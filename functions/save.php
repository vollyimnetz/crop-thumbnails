<?php
$cptSave = new CptSaveThumbnail();
add_action( 'wp_ajax_cptSaveThumbnail', array($cptSave, 'saveThumbnail') );

class CptSaveThumbnail {
	
	private $debug = array();
	
	/**
	 * Handle-function called via ajax request.
	 * Check and crop multiple Images. Update with wp_update_attachment_metadata if needed.
	 * Input parameters:
	 *    * $_REQUEST['selection'] - json-object - data of the selection/crop
	 *    * $_REQUEST['raw_values'] - json-object - data of the original image
	 *    * $_REQUEST['active_values'] - json-array - array with data of the images to crop
	 * The main code is wraped via try-catch - the errorMessage will send back to JavaScript for displaying in an alert-box.
	 * Called die() at the end. 
	 */
	function saveThumbnail() {
		global $cptSettings;
		$json_return = array();
		
		try {
			/** get data **/
			$upload_dir = wp_upload_dir();
			$tmp_dir = $upload_dir['basedir']."/tmp/";
			$options = $cptSettings->getOptions();
			//from $_REQUEST
			$selection = json_decode(stripcslashes($_REQUEST['selection']));
			$sourceImgData = json_decode(stripcslashes($_REQUEST['raw_values']));
			$targetImgData = json_decode(stripcslashes($_REQUEST['active_values']));
			
			//from DB
			$dbImageSizes = $cptSettings->getImageSizes();
			
			$obj = get_post($sourceImgData->id);
			$sourceImgPath = get_attached_file( $obj->ID );
			$post_metadata = wp_get_attachment_metadata($obj->ID, true);//get the attachement metadata of the post
			
			$this->validation($selection,$obj,$sourceImgPath,$post_metadata);
			
			
			#$debug.= "\nselection\n".print_r($selection,true);
			#$debug.= "\ntargetImgData\n".print_r($sourceImgData,true);
			#$debug.= "\ntargetImgData\n".print_r($targetImgData,true);
			#$debug.= "\nimageObject\n".print_r($obj,true);
			#$debug.= "\nsource:".$sourceImgPath."\n";
			
			/**
			 * will be true if the image format issnt in the attachements metadata, 
			 * and wordpress dont know about the image-file
			 */
			$_changed_image_format = false;
			$_processing_error = array();
			foreach($targetImgData as $_imageSize) {
				$this->addDebug('submitted image-data');
				$this->addDebug(print_r($_imageSize,true));
				$_delete_old_file = '';
				if(!$this->isImageSizeValid($_imageSize,$dbImageSizes)) {
					$this->addDebug("Image Size not valid.");
					continue;
				}
				if(empty($post_metadata['sizes'][$_imageSize->name])) {
					$_changed_image_format = true;
				} else {
					//the old size hasent got the right image-size/image-ratio --> delete it or nobody will ever delete it correct
					if($post_metadata['sizes'][$_imageSize->name]['width'] != intval($_imageSize->width)
							|| $post_metadata['sizes'][$_imageSize->name]['height'] != intval($_imageSize->height) ) {
							
						$_delete_old_file = $post_metadata['sizes'][$_imageSize->name]['file'];
						$_changed_image_format = true;		
					}
				}
				
				
				
				$_filepath = $this->generateFilename($sourceImgPath, $_imageSize->width, $_imageSize->height);
				$_filepath_info = pathinfo($_filepath);
				$_tmp_filepath = $tmp_dir.$_filepath_info['basename'];
				$this->addDebug("filename:".$_filepath);
				
				
				$crop_width = $_imageSize->width;
				$crop_height = $_imageSize->height;
				if(!$_imageSize->crop || $_imageSize->width==0 || $_imageSize->height==0 || $_imageSize->width==9999 || $_imageSize->height==9999) {
					//handle images with soft-crop width/height value and crop set to "true"
					$crop_width = $selection->x2 - $selection->x;
					$crop_height = $selection->y2 - $selection->y;
				}
				
				$result = wp_crop_image(		// * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
					intval($sourceImgData->id),	// * @param string|int $src The source file or Attachment ID.
					$selection->x,				// * @param int $src_x The start x position to crop from.
					$selection->y,				// * @param int $src_y The start y position to crop from.
					$selection->x2 - $selection->x,	// * @param int $src_w The width to crop.
					$selection->y2 - $selection->y,	// * @param int $src_h The height to crop.
					$crop_width,				// * @param int $dst_w The destination width.
					$crop_height,				// * @param int $dst_h The destination height.
					false,						// * @param int $src_abs Optional. If the source crop points are absolute.
					$_tmp_filepath				// * @param string $dst_file Optional. The destination file to write to.
				);
				
				$_error = false;
				if(empty($result)) {
					$_processing_error[] = sprintf(__('Cant generate filesize "%s".',CROP_THUMBS_LANG),$_imageSize->name);
					$_error = true;
				} else {
					if(!empty($_delete_old_file)) {
						@unlink($_filepath_info['dirname'].'/'.$_delete_old_file);
					}
					if(!@copy($result,$_filepath)) {
						$_processing_error[] = sprintf(__('Cant copy temporary file to media-library.',CROP_THUMBS_LANG));
						$_error = true;
					}
					if(!@unlink($result)) {
						$_processing_error[] = sprintf(__('Cant delete temporary file.',CROP_THUMBS_LANG));
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
					$post_metadata['sizes'][$_imageSize->name] = $_new_meta;
				} else {
					$this->addDebug('error on '.$_filepath_info['basename']);
					$this->addDebug(implode(' | ',$_processing_error));
				}
			}
			
			//we have to update the posts metadate
			//otherwise new sizes will not be updated
			wp_update_attachment_metadata( $obj->ID, $post_metadata);
			
			//generate result;
			$json_return['debug'] = $this->getDebugOutput($options);
			if(!empty($_processing_error)) {
				//one or more errors happend when generating thumbnails
				$json_return['processingErrors'] = implode("\n",$_processing_error); 
			}
			if($_changed_image_format) {
				//there was a change in the image-formats 
				$json_return['changed_image_format'] = true;
			}
			$json_return['success'] = time();//time for cache-breaker
			echo json_encode($json_return);
		} catch (Exception $e) {
			$json_return['debug'] = $this->getDebugOutput($options);
			$json_return['error'] = $e->getMessage();
			echo json_encode($json_return);
		}
		die();
	}

	private function addDebug($text) {
		$this->debug[] = $text;
	}
	
	private function getDebugOutput($options) {
		if(!empty($this->debug)) {
			return join("\n",$this->debug);
		}
		return '';
	}

	/**
	 * @param object data of the new ImageSize the user want to crop
	 * @param array all available ImageSizes
	 * @return boolean true if the newImageSize is in the list of ImageSizes and dimensions are correct
	 */
	function isImageSizeValid(&$submitted,$dbData) {
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
	 * some basic validations and value transformations
	 * @param array the user submitted selection
	 * @param object the loaded image-object loaded by $sourceImgData->id
	 * @param string the server-path to the source-image
	 * @param object metadata of the image-attachement
	 * @throw Exception if the security validation fails
	 */
	function validation($selection,$obj,$sourceImgPath,$post_metadata)  {
		global $cptSettings;
		if(!check_ajax_referer($cptSettings->getNonceBase(),'_ajax_nonce',false)) {
			throw new Exception(__("ERROR: Security Check failed (maybe a timeout - please try again).",CROP_THUMBS_LANG), 1);
		}
		
		if(!isset($selection->x) || !isset($selection->y) || !isset($selection->x2) || !isset($selection->y2)) {
			throw new Exception(__('ERROR: Submitted data are not complete.',CROP_THUMBS_LANG), 1);
		}
		$selection->x = intval($selection->x);
		$selection->y = intval($selection->y);
		$selection->x2 = intval($selection->x2);
		$selection->y2 = intval($selection->y2);
		
		if($selection->x < 0 || $selection->y < 0) {
			throw new Exception(__('A cropping with this dimensions on these Image ist not possible.',CROP_THUMBS_LANG), 1);
		}
		
		/**
		$_test = 20948;
		$obj = get_post($_test);
		$sourceImgPath = get_attached_file($_test);
		$post_metadata = wp_get_attachment_metadata($_test, true);//get the attachement metadata of the post
		*/
		
		if(empty($obj)) {
			throw new Exception(__('ERROR: Can`t find original Image in Database!',CROP_THUMBS_LANG), 1);
		}
		if(empty($sourceImgPath)) {
			throw new Exception(__('ERROR: Can`t find original Imagefile!',CROP_THUMBS_LANG), 1);
		}
		if(empty($post_metadata)) {
			throw new Exception(__('ERROR: Can`t find original Image-Metadata!',CROP_THUMBS_LANG), 1);
		}
	}

	/**
	 * Generate the Filename (and path) of the thumbnail based on width and height the same way as wordpress do.
	 * @see generate_filename in wp-includes/class-wp-image-editor.php
	 * @param string Path to the original (full-size) file.
	 * @param int width of the new image
	 * @param int height of the new image
	 * @return string path to the new image
	 */
	function generateFilename( $file, $w, $h ){
		$info = pathinfo($file);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = wp_basename($file, ".$ext");
		$suffix = "{$w}x{$h}";
		$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";
		
		return $destfilename;
	}
}
?>