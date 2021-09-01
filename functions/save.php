<?php

add_action('after_setup_theme', function() {
	//Add the ajax action for entring the cropping function.
	add_action( 'wp_ajax_cptSaveThumbnail', [CptSaveThumbnail::class, 'saveThumbnailAjaxWrap'], 10);

	//Add the crop_thumbnails_do_crop filter for the default behaiviour of the plugin.
	//You may exchange it with your own function, by removing the default filter and store your own.
	add_filter( 'crop_thumbnails_do_crop', [CptSaveThumbnail::class, 'filter_doWpCrop'], 10, 5);

	//add the function to determine if an old file should be deleted
	add_filter( 'crop_thumbnails_should_delete_old_file', [CptSaveThumbnail::class, 'filter_shouldDeleteOldFile'], 10, 4);
}, 10);

class CptSaveThumbnail {
	
	protected static $debug = [];
	
	/**
	 * Handle-function called via ajax request.
	 * Check and crop multiple images. Update with wp_update_attachment_metadata if needed.
	 * Input parameters:
	 *    * $_REQUEST['selection'] - json-object - data of the selection/crop
	 *    * $_REQUEST['raw_values'] - json-object - data of the original image
	 *    * $_REQUEST['activeImageSizes'] - json-array - array with data of the images to crop
	 * The main code is wraped via try-catch - the errorMessage will send back to JavaScript for displaying in an alert-box.
	 * Called die() at the end.
	 */
	public static function saveThumbnail() {
		$jsonResult = [];
		$settings = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		
		try {
			$input = self::getValidatedInput();
			self::addDebug('validated input data');
			self::addDebug($input);

			$sourceImgPath = get_attached_file( $input->sourceImageId );
			if(empty($sourceImgPath)) {
				throw new Exception(__("ERROR: Can't find original image file!",'crop-thumbnails'), 1);
			}
			
			
			$imageMetadata = wp_get_attachment_metadata($input->sourceImageId, true);//get the attachement metadata of the post
			if(empty($imageMetadata)) {
				throw new Exception(__("ERROR: Can't find original image metadata!",'crop-thumbnails'), 1);
			}
			
			//from DB
			$dbImageSizes = $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes();
			
			/**
			 * will be filled with the new image-url if the image format isn't in the attachements metadata, 
			 * and Wordpress doesn't know about the image file
			 */
			$changedImageName = [];
			$_processing_error = [];
			foreach($input->activeImageSizes as $activeImageSize) {
				if(!self::isImageSizeValid($activeImageSize,$dbImageSizes)) {
					self::addDebug("Image size not valid.");
					continue;
				}

				$croppedSize = self::getCroppedSize($activeImageSize,$imageMetadata,$input);
				
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
					$_processing_error[$activeImageSize->name][] = sprintf(__("Can't generate filesize '%s'.",'crop-thumbnails'), $activeImageSize->name);
					$_error = true;
				} else {
					if(!empty($oldFile_toDelete)) {
						self::addDebug("delete old image:".$oldFile_toDelete);
						@unlink($currentFilePathInfo['dirname'].DIRECTORY_SEPARATOR.$oldFile_toDelete);
					}
					if(!@copy($resultWpCropImage, $currentFilePath)) {
						$_processing_error[$activeImageSize->name][] = __("Can't copy temporary file to media library.", 'crop-thumbnails');
						$_error = true;
					}
					if(!@unlink($resultWpCropImage)) {
						$_processing_error[$activeImageSize->name][] = __("Can't delete temporary file.", 'crop-thumbnails');
						$_error = true;
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
			
			//we have to update the posts metadate
			//otherwise new sizes will not be updated
			$imageMetadata = apply_filters('crop_thumbnails_before_update_metadata', $imageMetadata, $input->sourceImageId);
			wp_update_attachment_metadata( $input->sourceImageId, $imageMetadata);
			
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
			echo json_encode($jsonResult);
		} catch (Exception $e) {
			if(!empty($settings['debug_data'])) {
				$jsonResult['debug'] = self::getDebug();
			}
			$jsonResult['error'] = $e->getMessage();
			echo json_encode($jsonResult);
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
		return wp_crop_image(								// * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
			$input->sourceImageId,							// * @param string|int $src The source file or Attachment ID.
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
		} catch(Exception $e) {
			$croppedWidth = 10;
			$croppedHeight = 10;
		}
		
		return ['width' => $croppedWidth, 'height'=> $croppedHeight];
	}
	
	/**
	 * This function is called by the WordPress-ajax-callback. Its only purpose is to call the
	 * saveThumbnail function and die().
	 * All WordPress ajax-functions should call the "die()" function in the end. But this makes
	 * phpunit tests impossible - so we have to wrap it.
	 */
	public static function saveThumbnailAjaxWrap() {
		self::saveThumbnail();
		die();
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
	 * @param array $croppingInput the input data for the cropping (to store the crop-informations)
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
			'x' => $croppingInput->selection->x,
			'y' => $croppingInput->selection->y,
			'x2' => $croppingInput->selection->x2,
			'y2' => $croppingInput->selection->y2,
			'original_width' => $imageMetadata['width'],
			'original_height' => $imageMetadata['height'],
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
		return $imageMetadata;
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
	protected static function getValidatedInput() {

		if(!check_ajax_referer($GLOBALS['CROP_THUMBNAILS_HELPER']->getNonceBase(),'_ajax_nonce',false)) {
			throw new Exception(__("ERROR: Security Check failed (maybe a timeout - please try again).",'crop-thumbnails'), 1);
		}
		
		
		if(empty($_REQUEST['crop_thumbnails'])) {
			throw new Exception(__('ERROR: Submitted data is incomplete.','crop-thumbnails'), 1);
		}
		$input = json_decode(stripcslashes($_REQUEST['crop_thumbnails']));
		
		
		if(empty($input->selection) || empty($input->sourceImageId) || !isset($input->activeImageSizes)) {
			throw new Exception(__('ERROR: Submitted data is incomplete.','crop-thumbnails'), 1);
		}
		
		if(!self::isUserPermitted($input->sourceImageId)) {
			throw new Exception(__("You are not permitted to crop the thumbnails.",'crop-thumbnails'), 1);
		}
		
		if(!isset($input->selection->x) || !isset($input->selection->y) || !isset($input->selection->x2) || !isset($input->selection->y2)) {
			throw new Exception(__('ERROR: Submitted data is incomplete.','crop-thumbnails'), 1);
		}
		
		
		$input->selection->x = intval($input->selection->x);
		$input->selection->y = intval($input->selection->y);
		$input->selection->x2 = intval($input->selection->x2);
		$input->selection->y2 = intval($input->selection->y2);
		
		if($input->selection->x < 0 || $input->selection->y < 0) {
			throw new Exception(__('Cropping to these dimensions on this image is not possible.','crop-thumbnails'), 1);
		}
		
		
		$input->sourceImageId = intval($input->sourceImageId);
		$_tmp = get_post($input->sourceImageId);//need to be its own var - cause of old php versions
		if(empty($_tmp)) {
			throw new Exception(__("ERROR: Can't find original image in database!",'crop-thumbnails'), 1);
		}
		
		return $input;
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
		$outputFormats = apply_filters('image_editor_output_format', [], $file);
		$fileTypeInformations = wp_check_filetype($file);
		if(isset($outputFormats[$fileTypeInformations['type']])) {
			$ext = array_search($outputFormats[$fileTypeInformations['type']], wp_get_mime_types(), true);
		}

		$name = wp_basename($file, '.'.$ext);
		if(!empty($imageMetadata['original_image'])) {
			$name = wp_basename($imageMetadata['original_image'], '.'.$ext);
		}
		$suffix = $w.'x'.$h;
		$destfilename = $dir.'/'.$name.'-'.$suffix.'.'.$ext;
		return apply_filters('crop_thumbnails_filename', $destfilename, $file, $w, $h, $crop, $info, $imageMetadata);
	}

	/**
	 * Check if the user is permitted to crop the thumbnails.
	 * 
	 * You may override the default result of this function by using the filter 'crop_thumbnails_user_permission_check'.
	 * 
	 * @param int $imageId The ID of the image that should be cropped (not used in default test)
	 * @return boolean true if the user is permitted
	 */
	public static function isUserPermitted($imageId) {
		$return = false;
		$cropThumbnailSettings = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		if(current_user_can('edit_files')) {
			$return = true;
		}
		if(current_user_can('upload_files') && empty($cropThumbnailSettings['user_permission_only_on_edit_files'])) {
			$return = true;
		}
		return apply_filters('crop_thumbnails_user_permission_check', $return, $imageId);
	}
}
