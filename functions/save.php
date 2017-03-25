<?php
$cptSave = new CptSaveThumbnail();
add_action( 'wp_ajax_cptSaveThumbnail', array($cptSave, 'saveThumbnailAjaxWrap') );

class CptSaveThumbnail {
	
	private static $debug = array();
	
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
	public function saveThumbnail() {
		global $cptSettings;
		$jsonResult = array();
		
		try {
			$input = $this->getValidatedInput();
			
			
			$sourceImgPath = get_attached_file( $input->sourceImageId );
			if(empty($sourceImgPath)) {
				throw new Exception(__("ERROR: Can't find original image file!",CROP_THUMBS_LANG), 1);
			}
			
			
			$postMetadata = wp_get_attachment_metadata($input->sourceImageId, true);//get the attachement metadata of the post
			if(empty($postMetadata)) {
				throw new Exception(__("ERROR: Can't find original image metadata!",CROP_THUMBS_LANG), 1);
			}
			
			//from DB
			$dbImageSizes = $cptSettings->getImageSizes();
			
			
			/**
			 * will be filled with the new image-url if the image format isn't in the attachements metadata, 
			 * and Wordpress doesn't know about the image file
			 */
			$_changed_image_format = array();
			$_processing_error = array();
			foreach($input->activeImageSizes as $_imageSize) {
				self::addDebug('submitted image-data');
				self::addDebug(print_r($_imageSize,true));
				$_delete_old_file = '';
				if(!self::isImageSizeValid($_imageSize,$dbImageSizes)) {
					self::addDebug("Image size not valid.");
					continue;
				}
				if(empty($postMetadata['sizes'][$_imageSize->name])) {
					$_changed_image_format[ $_imageSize->name ] = true;
				} else {
					//the old size hasent got the right image-size/image-ratio --> delete it or nobody will ever delete it correct
					if($postMetadata['sizes'][$_imageSize->name]['width'] != intval($_imageSize->width) || $postMetadata['sizes'][$_imageSize->name]['height'] != intval($_imageSize->height) ) {
						$_delete_old_file = $postMetadata['sizes'][$_imageSize->name]['file'];
						$_changed_image_format[ $_imageSize->name ] = true;
					}
				}
				
				$_filepath = self::generateFilename($sourceImgPath, $_imageSize->width, $_imageSize->height);
				$_filepath_info = pathinfo($_filepath);
				
				$_tmp_filepath = $cptSettings->getUploadDir().DIRECTORY_SEPARATOR.$_filepath_info['basename'];
				self::addDebug("filename:".$_filepath);
				
				
				$crop_width = $_imageSize->width;
				$crop_height = $_imageSize->height;
				if(!$_imageSize->crop || $_imageSize->width==0 || $_imageSize->height==0 || $_imageSize->width==9999 || $_imageSize->height==9999) {
					//handle images with soft-crop width/height value and crop set to "true"
					$crop_width = $input->selection->x2 - $input->selection->x;
					$crop_height = $input->selection->y2 - $input->selection->y;
				}
				
				$result = wp_crop_image(		// * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
					$input->sourceImageId,	// * @param string|int $src The source file or Attachment ID.
					$input->selection->x,				// * @param int $src_x The start x position to crop from.
					$input->selection->y,				// * @param int $src_y The start y position to crop from.
					$input->selection->x2 - $input->selection->x,	// * @param int $src_w The width to crop.
					$input->selection->y2 - $input->selection->y,	// * @param int $src_h The height to crop.
					$crop_width,				// * @param int $dst_w The destination width.
					$crop_height,				// * @param int $dst_h The destination height.
					false,						// * @param int $src_abs Optional. If the source crop points are absolute.
					$_tmp_filepath				// * @param string $dst_file Optional. The destination file to write to.
				);
				
				$_error = false;
				if(empty($result)) {
					$_processing_error[] = sprintf(__("Can't generate filesize '%s'.",CROP_THUMBS_LANG),$_imageSize->name);
					$_error = true;
				} else {
					if(!empty($_delete_old_file)) {
						@unlink($_filepath_info['dirname'].DIRECTORY_SEPARATOR.$_delete_old_file);
					}
					if(!@copy($result,$_filepath)) {
						$_processing_error[] = sprintf(__("Can't copy temporary file to media library.",CROP_THUMBS_LANG));
						$_error = true;
					}
					if(!@unlink($result)) {
						$_processing_error[] = sprintf(__("Can't delete temporary file.",CROP_THUMBS_LANG));
						$_error = true;
					}
				}
				
				if(!$_error) {
					//update metadata --> otherwise new sizes will not be updated
					$_new_meta = array(
						'file'=>$_filepath_info['basename'],
						'width'=>intval($crop_width),
						'height'=>intval($crop_height));
					if(!empty($dbImageSizes[$_imageSize->name]['crop'])) {
						$_new_meta['crop'] = $dbImageSizes[$_imageSize->name]['crop'];
					}
					$postMetadata['sizes'][$_imageSize->name] = $_new_meta;
					
					$_full_filepath = trailingslashit($_filepath_info['dirname']) . $_filepath_info['basename'];
					do_action('crop_thumbnails_after_save_new_thumb', $_full_filepath, $_imageSize->name, $_new_meta );
					
					//return the new file location
					if(!empty($_changed_image_format[ $_imageSize->name ])) {
						$orig_img = wp_get_attachment_image_src($input->sourceImageId, $_imageSize->name);
						$_changed_image_format[ $_imageSize->name ] = $orig_img[0];
					}
				} else {
					self::addDebug('error on '.$_filepath_info['basename']);
					self::addDebug(implode(' | ',$_processing_error));
				}
			}//END foreach
			
			//we have to update the posts metadate
			//otherwise new sizes will not be updated
			$postMetadata = apply_filters('crop_thumbnails_before_update_metadata', $postMetadata, $input->sourceImageId);
			wp_update_attachment_metadata( $input->sourceImageId, $postMetadata);
			
			//generate result;
			$jsonResult['debug'] = self::getDebugOutput();
			if(!empty($_processing_error)) {
				//one or more errors happend when generating thumbnails
				$jsonResult['processingErrors'] = implode("\n",$_processing_error); 
			}
			if(!empty($_changed_image_format)) {
				//there was a change in the image-formats 
				$jsonResult['changed_image_format'] = $_changed_image_format;
			}
			$jsonResult['success'] = time();//time for cache-breaker
			echo json_encode($jsonResult);
		} catch (Exception $e) {
			$jsonResult['debug'] = self::getDebugOutput();
			$jsonResult['error'] = $e->getMessage();
			echo json_encode($jsonResult);
		}
	}
	
	/**
	 * This function is called by the wordpress-ajax-callback. Its only purpose is to call the
	 * saveThumbnail function and die().
	 * All wordpress ajax-functions should call the "die()" function in the end. But this makes
	 * phpunit tests impossible - so we have to wrap it.
	 */
	public function saveThumbnailAjaxWrap() {
		$this->saveThumbnail();
		die();
	}

	private static function addDebug($text) {
		self::$debug[] = $text;
	}
	
	private static function getDebugOutput() {
		if(!empty(self::$debug)) {
			return self::$debug;
		}
		return [];
	}

	/**
	 * @param object data of the new ImageSize the user want to crop
	 * @param array all available ImageSizes
	 * @return boolean true if the newImageSize is in the list of ImageSizes and dimensions are correct
	 */
	private static function isImageSizeValid(&$submitted,$dbData) {
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
	private function getValidatedInput() {
		global $cptSettings;
		
		if(!check_ajax_referer($cptSettings->getNonceBase(),'_ajax_nonce',false)) {
			throw new Exception(__("ERROR: Security Check failed (maybe a timeout - please try again).",CROP_THUMBS_LANG), 1);
		}
		
		
		if(empty($_REQUEST['crop_thumbnails'])) {
			throw new Exception(__('ERROR: Submitted data is incomplete.',CROP_THUMBS_LANG), 1);
		}
		$input = json_decode(stripcslashes($_REQUEST['crop_thumbnails']));
		
		
		if(empty($input->selection) || empty($input->sourceImageId) || !isset($input->activeImageSizes)) {
			throw new Exception(__('ERROR: Submitted data is incomplete.',CROP_THUMBS_LANG), 1);
		}
		
		
		if(!isset($input->selection->x) || !isset($input->selection->y) || !isset($input->selection->x2) || !isset($input->selection->y2)) {
			throw new Exception(__('ERROR: Submitted data is incomplete.',CROP_THUMBS_LANG), 1);
		}
		
		
		$input->selection->x = intval($input->selection->x);
		$input->selection->y = intval($input->selection->y);
		$input->selection->x2 = intval($input->selection->x2);
		$input->selection->y2 = intval($input->selection->y2);
		
		if($input->selection->x < 0 || $input->selection->y < 0) {
			throw new Exception(__('Cropping to these dimensions on this image is not possible.',CROP_THUMBS_LANG), 1);
		}
		
		
		$input->sourceImageId = intval($input->sourceImageId);
		if(empty(get_post($input->sourceImageId))) {
			throw new Exception(__("ERROR: Can't find original image in database!",CROP_THUMBS_LANG), 1);
		}
		
		return $input;
	}


	/**
	 * Generate the Filename (and path) of the thumbnail based on width and height the same way as wordpress do.
	 * @see generate_filename in wp-includes/class-wp-image-editor.php
	 * @param string Path to the original (full-size) file.
	 * @param int width of the new image
	 * @param int height of the new image
	 * @return string path to the new image
	 */
	private static function generateFilename( $file, $w, $h ){
		$info = pathinfo($file);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = wp_basename($file, '.'.$ext);
		$suffix = $w.'x'.$h;
		$destfilename = $dir.'/'.$name.'-'.$suffix.'.'.$ext;
		
		return $destfilename;
	}
}
?>
