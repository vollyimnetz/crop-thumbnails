<?php
namespace crop_thumbnails;

defined( 'ABSPATH' ) || exit;


add_action('after_setup_theme', function() {
	//Add the crop_thumbnails_do_crop filter for the default behaiviour of the plugin.
	//You may exchange it with your own function, by removing the default filter and store your own.
	add_filter( 'crop_thumbnails_do_crop', [CptSaveThumbnail::class, 'filter_doWpCrop'], 10, 5);

	//add the function to determine if an old file should be deleted
	add_filter( 'crop_thumbnails_should_delete_old_file', [CptSaveThumbnail::class, 'filter_shouldDeleteOldFile'], 10, 4);
}, 10);

class CptSaveThumbnail {

	protected static $debug = [];

	public static function checkRestPermission() {
		$return = false;
		$cropThumbnailSettings = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		if(current_user_can('edit_files')) {
			$return = true;
		}
		if(current_user_can('upload_files') && empty($cropThumbnailSettings['user_permission_only_on_edit_files'])) {
			$return = true;
		}
		return $return;
	}

	/**
	 * Handle-function called via ajax request.
	 * Check and crop multiple images. Update with wp_update_attachment_metadata if needed.
	 * Input parameters:
	 *    * $_REQUEST['selection'] - json-object - data of the selection/crop
	 *    * $_REQUEST['sourceImageId'] - int - the ID of the original image
	 *    * $_REQUEST['activeImageSizes'] - json-array - array with data of the images to crop
	 * The main code is wraped via try-catch - the errorMessage will send back to JavaScript for displaying in an alert-box.
	 * @return array JSON-Object with the result of the cropping
	 */
	public static function saveThumbnail($request) {
		if(!function_exists('wp_crop_image')) require_once(ABSPATH . 'wp-admin/includes/image.php');

		$jsonResult = [];
		$settings = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		try {
			$input = self::getValidatedInput($request);
			self::addDebug('validated input data');
			self::addDebug($input);

			$sourceImgPath = get_attached_file( $input->sourceImageId );
			if(empty($sourceImgPath)) {
				throw new \Exception(__("ERROR: Can't find original image file!",'crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
			}

			//fails early (before anything is changed) if the original is missing and the user has not confirmed to crop from the smaller image
			self::getCropSourcePath($input);


			$imageMetadata = wp_get_attachment_metadata($input->sourceImageId, true);//get the attachement metadata of the post
			if(empty($imageMetadata)) {
				throw new \Exception(__("ERROR: Can't find original image metadata!",'crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
			}

			//from DB
			$dbImageSizes = $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes();

			/**
			 * will be filled with the new image-url if the image format isn't in the attachements metadata,
			 * and Wordpress doesn't know about the image file
			 */
			$changedImageName = [];
			$_processing_error = [];
			/**
			 * Old files that are no longer needed by the size they belonged to.
			 * They are deleted after all sizes are processed, and only if no size in the final metadata references them
			 * (WordPress shares one file between sizes with identical dimensions).
			 */
			$oldFilesToDelete = [];

			$_activeImageSizes = apply_filters('crop_thumbnails_active_image_sizes', $input->activeImageSizes);
			foreach($_activeImageSizes as $activeImageSize) {
				if(!self::isImageSizeValid($activeImageSize,$dbImageSizes)) {
					self::addDebug("Image size not valid.");
					continue;
				}

				$croppedSize = self::getCroppedSize($activeImageSize, $imageMetadata, $input);

				$currentFilePath = self::generateFilename($sourceImgPath, $imageMetadata, $croppedSize['width'], $croppedSize['height'], $activeImageSize->crop);
				self::addDebug("filename: ".$currentFilePath);
				$currentFilePathInfo = pathinfo($currentFilePath);
				$currentFilePathInfo['basename'] = wp_basename($currentFilePath);//uses the i18n version of the file-basename
				$temporaryCopyFile = $GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir().DIRECTORY_SEPARATOR.$currentFilePathInfo['basename'];

				do_action('crop_thumbnails_before_crop', $input, $croppedSize, $temporaryCopyFile, $currentFilePath);
				$resultWpCropImage = apply_filters('crop_thumbnails_do_crop', null, $input, $croppedSize, $temporaryCopyFile, $currentFilePath);
				do_action('crop_thumbnails_after_crop', $input, $croppedSize, $temporaryCopyFile, $currentFilePath, $resultWpCropImage);

				$oldFile_toDelete = '';
				if(empty($imageMetadata['sizes'][$activeImageSize->name])) {
					//image-size not yet existant
					self::addDebug('Image filename has changed ('.$activeImageSize->name . ')');
					$changedImageName[ $activeImageSize->name ] = true;
				} elseif( apply_filters('crop_thumbnails_should_delete_old_file',
								false,//default value
								$imageMetadata['sizes'][$activeImageSize->name],
								$activeImageSize,
								$currentFilePath
						) ) {
					//the old file of this image-size needs to be deleted
					$oldFile_toDelete = $imageMetadata['sizes'][$activeImageSize->name]['file'];
					$changedImageName[ $activeImageSize->name ] = true;
				}

				$_error = false;
				if(empty($resultWpCropImage) || is_wp_error($resultWpCropImage)) {
					/* translators: %s: name of the image size */
					$_processing_error[$activeImageSize->name][] = sprintf(__("Can't generate filesize '%s'.",'crop-thumbnails'), $activeImageSize->name);
					$_error = true;
				} else {
					if(!empty($oldFile_toDelete)) {
						$oldFilesToDelete[] = $currentFilePathInfo['dirname'].DIRECTORY_SEPARATOR.$oldFile_toDelete;
					}
					// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- moves the temporary crop into the media library, WP_Filesystem would need credentials in this context
					if(!@rename($resultWpCropImage, $currentFilePath)) {
						$_processing_error[$activeImageSize->name][] = __("Can't copy temporary file to media library.", 'crop-thumbnails');
						$_error = true;
					}
					if(file_exists($resultWpCropImage)) {
						wp_delete_file($resultWpCropImage);
						if(file_exists($resultWpCropImage)) {
							$_processing_error[$activeImageSize->name][] = __("Can't delete temporary file.", 'crop-thumbnails');
							$_error = true;
						}
					}
				}

				if(!$_error) {
					//update metadata --> otherwise new sizes will not be updated
					$imageMetadata = self::createNewMetadata($imageMetadata, $activeImageSize->name, $currentFilePathInfo, $croppedSize['width'], $croppedSize['height'], $input);
				} else {
					self::addDebug('error on '.$currentFilePathInfo['basename']);
					self::addDebug($_processing_error);
				}
			}//END foreach

			self::deleteUnusedFiles($oldFilesToDelete, $imageMetadata);

			//we have to update the posts metadate
			//otherwise new sizes will not be updated
			$imageMetadata = apply_filters('crop_thumbnails_before_update_metadata', $imageMetadata, $input->sourceImageId);
			wp_update_attachment_metadata( $input->sourceImageId, $imageMetadata);
			self::addDebug('metadata updated:');
			self::addDebug($imageMetadata);

			//generate result;
			if(!empty($changedImageName)) {
				//there was a change in the image-formats
				foreach($changedImageName as $key=>$value) {
					$newImageLocation = wp_get_attachment_image_src($input->sourceImageId, $key);
					$changedImageName[ $key ] = $newImageLocation[0];
				}
				$jsonResult['changedImageName'] = $changedImageName;
			}
			if(!empty($_processing_error)) {//one or more errors happend when generating thumbnails
				$jsonResult['processingErrors'] = $_processing_error;
			}
			if(!empty($settings['debug_data'])) {
				$jsonResult['debug'] = self::getDebug();
			}
			$jsonResult['success'] = time();//time for cache-breaker
			return $jsonResult;
		} catch (\Exception $e) {
			if(!empty($settings['debug_data'])) {
				$jsonResult['debug'] = self::getDebug();
			}
			$jsonResult['error'] = $e->getMessage();
			return $jsonResult;
		} catch (\Throwable $e) {
			//PHP errors (i.e. TypeError, ValueError, DivisionByZeroError) are no Exceptions - without this the request would end in an empty HTTP 500
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- the server log is the only place for this information (the details should not be sent to the browser)
			error_log('crop-thumbnails: '.get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
			if(!empty($settings['debug_data'])) {
				$jsonResult['debug'] = self::getDebug();
			}
			$jsonResult['error'] = __('An unknown error occured.','crop-thumbnails');
			return $jsonResult;
		}
	}

	/**
	 * Delete the given files, but keep every file that is still referenced by the metadata
	 * (main file, original image or any image size - WordPress shares files between sizes with identical dimensions).
	 *
	 * @param string[] $files full paths of the deletion candidates
	 * @param array $imageMetadata the final (updated) attachment metadata
	 */
	protected static function deleteUnusedFiles($files, $imageMetadata) {
		$used = [];
		if(!empty($imageMetadata['file'])) {
			$used[] = wp_basename($imageMetadata['file']);
		}
		if(!empty($imageMetadata['original_image'])) {
			$used[] = wp_basename($imageMetadata['original_image']);
		}
		if(!empty($imageMetadata['sizes']) && is_array($imageMetadata['sizes'])) {
			foreach($imageMetadata['sizes'] as $size) {
				if(!empty($size['file'])) {
					$used[] = wp_basename($size['file']);
				}
			}
		}

		foreach(array_unique($files) as $file) {
			if(in_array(wp_basename($file), $used, true)) {
				self::addDebug('keep old image (still in use):'.$file);
				continue;
			}
			self::addDebug('delete old image:'.$file);
			wp_delete_file($file);
		}
	}

	/**
	 * Filter function to determine whether we should delete old thumbnail file.
	 *
	 * We should delete when any of these happens:
	 *    - the old size hasn't got the right image-size/image-ratio
	 *    - the new image has a different file path
	 *
	 * Otherwise, nobody will ever delete it correctly.
	 *
	 * @param  bool   $baseResult          Filter base value
	 * @param  array  $oldSizeMetadata     The old image size from the database
	 * @param  object $activeImageSize     The image size that should be used
	 * @param  string $activeImageFilePath Full path to the new image
	 * @return bool
	 */
	public static function filter_shouldDeleteOldFile($baseResult, $oldSizeMetadata, $activeImageSize, $activeImageFilePath) {
		$result = absint($oldSizeMetadata['width']) !== absint($activeImageSize->width)
			|| absint($oldSizeMetadata['height']) !== absint($activeImageSize->height)
			|| wp_basename($activeImageFilePath) !== $oldSizeMetadata['file'];
		//error_log('filter_shouldDeleteOldFile: '. ($result ? 'YES' : 'NO') );
		return $result;
	}

	/**
	 * This is the place where crop-thumbnails crops the images - using the wordpress default function.
	 *
	 * @param bool   $baseResult          Filter base value
	 * @param object $input               Input object
	 * @param object $croppedSize         Target size of the result image
	 * @param object $temporaryCopyFile   Target file-path
	 * @param object $currentFilePath     Additional file-path of the current image
	 *
	 */
	public static function filter_doWpCrop($baseResult, $input, $croppedSize, $temporaryCopyFile, $currentFilePath) {
		$input = apply_filters('crop_thumbnails_optimize_input_before_crop', $input);

		$src = self::getCropSourcePath($input);
		return \wp_crop_image(								// * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
			$src,											// * @param string|int $src The source file or Attachment ID.
			$input->selection->x,							// * @param int $src_x The start x position to crop from.
			$input->selection->y,							// * @param int $src_y The start y position to crop from.
			$input->selection->x2 - $input->selection->x,	// * @param int $src_w The width to crop.
			$input->selection->y2 - $input->selection->y,	// * @param int $src_h The height to crop.
			$croppedSize['width'],							// * @param int $dst_w The destination width.
			$croppedSize['height'],							// * @param int $dst_h The destination height.
			false,											// * @param int $src_abs Optional. If the source crop points are absolute.
			$temporaryCopyFile								// * @param string $dst_file Optional. The destination file to write to.
		);
	}

	/**
	 * Is the original image (the file before WordPress has scaled it down) missing, while the scaled image file still exists?
	 * Only in this case it is possible to crop from the smaller (scaled) image.
	 *
	 * Use the filter 'crop_thumbnails_allow_cropping_from_scaled_image' (default true) to disable the fallback.
	 *
	 * @param int $imageId the attachment ID
	 * @return boolean
	 */
	public static function isOriginalMissingButScaledImageAvailable($imageId) {
		$originalPath = wp_get_original_image_path($imageId);
		if(!empty($originalPath) && is_readable($originalPath)) {
			return false;//original is available
		}
		$scaledPath = get_attached_file($imageId);
		if(empty($scaledPath) || !is_readable($scaledPath)) {
			return false;
		}
		if($originalPath === $scaledPath) {
			return false;//this image was never scaled - the "original" is the attached file itself
		}
		return (bool) apply_filters('crop_thumbnails_allow_cropping_from_scaled_image', true, $imageId);
	}

	/**
	 * The file to crop from: the original image - or, if the original is missing and the user has confirmed it, the scaled image.
	 * @param object $input the validated input (property "acceptSmallerSource" is the confirmation of the user)
	 * @return string path to the source file
	 * @throws \Exception if the original is missing and the cropping from the smaller image is not confirmed
	 */
	public static function getCropSourcePath($input) {
		if(self::isOriginalMissingButScaledImageAvailable($input->sourceImageId)) {
			if(empty($input->acceptSmallerSource) || $input->acceptSmallerSource!==true) {
				throw new \Exception(__('ERROR: The original image is missing. Cropping from the smaller image has to be confirmed.','crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
			}
			return get_attached_file($input->sourceImageId);
		}
		return wp_get_original_image_path($input->sourceImageId);
	}

	/**
	 * Get the end-size of the cropped image in pixels.
	 * Attention: these sizes are used to name the file.
	 * @param  object  $activeImageSize The image size that should be used
	 * @param  [type]  $imageMetadata   [description]
	 * @param  [type]  $input           [description]
	 * @return {[type]                  [description]
	 */
	public static function getCroppedSize($activeImageSize,$imageMetadata,$input) {
		//set target size of the cropped image
		$croppedWidth = $activeImageSize->width;
		$croppedHeight = $activeImageSize->height;
		try {
			if($activeImageSize->width===9999) {
				$croppedWidth = intval($imageMetadata['width']);
			} elseif($activeImageSize->height===9999) {
				$croppedHeight = intval($imageMetadata['height']);
			} elseif(intval($activeImageSize->width)===0 && intval($activeImageSize->height)===0) {
				$croppedWidth = $input->selection->x2 - $input->selection->x;
				$croppedHeight = $input->selection->y2 - $input->selection->y;
			} elseif(intval($activeImageSize->width)===0) {
				$croppedWidth = intval(( intval($imageMetadata['width']) / intval($imageMetadata['height']) ) * $activeImageSize->height);
				$croppedHeight = $activeImageSize->height;
			} elseif(intval($activeImageSize->height)===0) {
				$croppedWidth = $activeImageSize->width;
				$croppedHeight = intval(( intval($imageMetadata['height']) / intval($imageMetadata['width']) ) * $activeImageSize->width);
			}

			/* --- no need to use that ---
			if(!$activeImageSize->crop) {
				$croppedWidth = $input->selection->x2 - $input->selection->x;
				$croppedHeight = $input->selection->y2 - $input->selection->y;
			}*/
		} catch(\Exception $e) {
			$croppedWidth = 10;
			$croppedHeight = 10;
		}

		return ['width' => $croppedWidth, 'height'=> $croppedHeight];
	}

	protected static function addDebug($text) {
		self::$debug[] = $text;
	}

	protected static function getDebug() {
		if(!empty(self::$debug)) {
			return self::$debug;
		}
		return [];
	}

	/**
	 * Update the metadata for one image-size.
	 *
	 * @param array $imageMetadata the image-metadata base array to modify
	 * @param string $imageSizeName the name of the image-size
	 * @param array $currentFilePathInfo pathinfo of the new thumbnail/image-size
	 * @param int $croppedWidth the new width of the image
	 * @param int $croppedHeight the new height of the image
	 * @param object $croppingInput the input data for the cropping (to store the crop-informations)
	 * @return array the modified $imageMetadata
	 */
	protected static function createNewMetadata($imageMetadata, $imageSizeName, $currentFilePathInfo, $croppedWidth, $croppedHeight, $croppingInput) {
		$fullFilePath = trailingslashit($currentFilePathInfo['dirname']) . $currentFilePathInfo['basename'];

		$fileTypeInformations = wp_check_filetype($fullFilePath);

		$newValues = [];
		$newValues['file'] = $currentFilePathInfo['basename'];
		$newValues['width'] = intval($croppedWidth);
		$newValues['height'] = intval($croppedHeight);
		$newValues['mime-type'] = $fileTypeInformations['type'];
		$newValues['cpt_last_cropping_data'] = [
			'version' => 2,//the version of the following data --> version 1 had no version information
			/**
			 * version 1 had no version information and contained the following data:
			 * 'x' => $croppingInput->selection->x,
			 * 'y' => $croppingInput->selection->y,
			 * 'x2' => $croppingInput->selection->x2,
			 * 'y2' => $croppingInput->selection->y2,
			 * 'original_width' => $imageMetadata['width'],
			 * 'original_height' => $imageMetadata['height'],
			 *
			 * version 2 contains the following data:
			 * 'version' => 2,
			 * 'x' => $croppingInput->selection->x,
			 * 'y' => $croppingInput->selection->y,
			 * 'x2' => $croppingInput->selection->x2,
			 * 'y2' => $croppingInput->selection->y2
			 * original_width and original_height are not stored anymore, as the plugin will always use the original image for cropping
			 */

			'x' => $croppingInput->selection->x,
			'y' => $croppingInput->selection->y,
			'x2' => $croppingInput->selection->x2,
			'y2' => $croppingInput->selection->y2
		];

		$oldValues = [];
		if(empty($imageMetadata['sizes'])) {
			$imageMetadata['sizes'] = [];
		}
		if(!empty($imageMetadata['sizes'][$imageSizeName])) {
			$oldValues = $imageMetadata['sizes'][$imageSizeName];
		}
		$imageMetadata['sizes'][$imageSizeName] = array_merge($oldValues,$newValues);

		do_action('crop_thumbnails_after_save_new_thumb', $fullFilePath, $imageSizeName, $imageMetadata['sizes'][$imageSizeName] );

		return apply_filters('crop_thumbnails_create_new_metadata', $imageMetadata, $imageSizeName, $currentFilePathInfo, $croppedWidth, $croppedHeight, $croppingInput );
	}

	/**
	 * @param object data of the new ImageSize the user want to crop
	 * @param array all available ImageSizes
	 * @return boolean true if the newImageSize is in the list of ImageSizes and dimensions are correct
	 */
	protected static function isImageSizeValid(&$submitted,$dbData) {
		if(empty($submitted->name)) {
			return false;
		}
		if(empty($dbData[$submitted->name])) {
			return false;
		}

		//restore the default data just to make sure nothing is compromited
		$submitted->crop = empty($dbData[$submitted->name]['crop']) ? 0 : 1;
		$submitted->width = $dbData[$submitted->name]['width'];
		$submitted->height = $dbData[$submitted->name]['height'];
		//eventually we want to test some more later
		return true;
	}

	/**
	 * Some basic validations and value transformations
	 * @return object JSON-Object with submitted data
	 * @throw Exception if the security validation fails
	 */
	protected static function getValidatedInput($request) {
		$input = json_decode( $request->get_body(), false );
		if(empty($input) || empty($input->crop_thumbnails)) {
			throw new \Exception(__('ERROR: Submitted data is incomplete.','crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
		}

		$input = $input->crop_thumbnails;
		$input = apply_filters('crop_thumbnails_before_get_validated_input', $input);

		if(empty($input->selection) || empty($input->sourceImageId) || !isset($input->activeImageSizes)) {
			throw new \Exception(__('ERROR: Submitted data is incomplete.','crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
		}

		if(!self::isUserPermitted($input->sourceImageId)) {
			throw new \Exception(__("You are not permitted to crop the thumbnails.",'crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
		}

		if(!isset($input->selection->x) || !isset($input->selection->y) || !isset($input->selection->x2) || !isset($input->selection->y2)) {
			throw new \Exception(__('ERROR: Submitted data is incomplete.','crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
		}


		$input->selection->x = intval($input->selection->x);
		$input->selection->y = intval($input->selection->y);
		$input->selection->x2 = intval($input->selection->x2);
		$input->selection->y2 = intval($input->selection->y2);

		if($input->selection->x < 0) $input->selection->x = 0;
		if($input->selection->y < 0) $input->selection->y = 0;
		if($input->selection->x2 < 0) $input->selection->x2 = 0;
		if($input->selection->y2 < 0) $input->selection->y2 = 0;

		$input->sourceImageId = intval($input->sourceImageId);
		$_tmp = get_post($input->sourceImageId);//need to be its own var - cause of old php versions
		if(empty($_tmp) || $_tmp->post_type!=='attachment') {
			throw new \Exception(__("ERROR: Can't find original image in database!",'crop-thumbnails'), 1);// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is sent as JSON and rendered as text by the editor
		}

		return apply_filters('crop_thumbnails_after_get_validated_input', $input);
	}


	/**
	 * Generate the Filename (and path) of the thumbnail based on width and height the same way as WordPress do.
	 * @see generate_filename in wp-includes/class-wp-image-editor.php
	 * @param string $file Path to the original (full-size) file.
	 * @param array $imageMetadata the WordPress image-metadata array
	 * @param int $width width of the new image
	 * @param int $height height of the new image
	 * @param boolean $crop is this a cropped image-size
	 * @return string path to the new image
	 */
	protected static function generateFilename( $file, $imageMetadata, $w, $h, $crop ){
		$info = pathinfo($file);
		$dir = $info['dirname'];
		$ext = $info['extension'];

		/**
		 * since WordPress 5.8 the image extension / MIME type may differ from that of the
		 * original file so we'll use the below hook to check if any defaults are overwritten.
		 */
		$fileTypeInformations = wp_check_filetype($file);
		$outputFormats = apply_filters('image_editor_output_format', [], $file, $fileTypeInformations['type']);// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter
		if(isset($outputFormats[$fileTypeInformations['type']])) {
			$ext = array_search($outputFormats[$fileTypeInformations['type']], wp_get_mime_types(), true);
		}

		//the base name has to be taken with the extension of the source file (not the output extension)
		$name = wp_basename($file, '.'.$info['extension']);
		if(!empty($imageMetadata['original_image'])) {
			$name = wp_basename($imageMetadata['original_image'], '.'.pathinfo($imageMetadata['original_image'], PATHINFO_EXTENSION));
		}
		$suffix = $w.'x'.$h;
		$destfilename = $dir.'/'.$name.'-'.$suffix.'.'.$ext;
		return apply_filters('crop_thumbnails_filename', $destfilename, $file, $w, $h, $crop, $info, $imageMetadata);
	}

	/**
	 * Check if the user is permitted to crop the thumbnails of the given image.
	 *
	 * Two checks have to pass:
	 *  - the global check (see checkRestPermission) - the user is allowed to use the plugin at all
	 *  - the object check - the user is allowed to edit this specific attachment (i.e. an author is not allowed to crop images of other users)
	 *
	 * You may override the default result of this function by using the filter 'crop_thumbnails_user_permission_check'.
	 *
	 * @param int $imageId The ID of the image that should be cropped
	 * @return boolean true if the user is permitted
	 */
	public static function isUserPermitted($imageId) {
		$return = self::checkRestPermission();
		if($return) {
			$return = self::checkImagePermission($imageId);
		}
		return apply_filters('crop_thumbnails_user_permission_check', $return, $imageId);
	}

	/**
	 * Object based permission check - may the current user edit this attachment?
	 * @param int $imageId the attachment-id
	 * @return boolean
	 */
	protected static function checkImagePermission($imageId) {
		$imageId = absint($imageId);
		if(empty($imageId)) {
			return false;
		}
		return (bool) current_user_can('edit_post', $imageId);
	}
}
