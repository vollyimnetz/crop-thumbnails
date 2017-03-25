<?php

/**
 * Contains all code inside the croping-window
 */

class CPT_ForbiddenException extends Exception {}

class CropPostThumbnailsEditor {

	private $debugOutput = '';

	function __construct() {
		add_action('wp_ajax_cpt_cropdata', array($this, 'provideCropData') );
	}
	
	public function provideCropData() {
		try {
			header('Content-Type: application/json; charset=UTF-8');
			$result = $this->getCropData();
			echo json_encode($result);
		} catch(InvalidArgumentException $e) {
			http_response_code(400);
			echo 'FAILURE while processing request: '.$e->getMessage();
		} catch(CPT_ForbiddenException $e) {
			http_response_code(403);
			echo 'ERROR not allowed.';
		} catch(Exception $e) {
			http_response_code(400);
			echo 'FAILURE while processing request.';
		}
		die();//to prevent to send back a "0"
	}
	
	public function getCropData() {
		if(!self::isUserPermitted()) {
			throw new CPT_ForbiddenException();
		}
		
		global $cptSettings;
		$options = $cptSettings->getOptions();
		$result = array(
			'options' => $options,
			'imageObj' => null,
			'postTypeFilter' => null,
			'imageSizes' => array_values($cptSettings->getImageSizes()),
			'fullSizeImage' => null, //???
			'lang' => array(
				'bug' => __('Bug--this should not have occurred.',CROP_THUMBS_LANG),
				'warningOriginalToSmall' => __('Warning: the original image is too small to be cropped in good quality with this thumbnail size.',CROP_THUMBS_LANG),
				'selectOne' => __('First, select an image. Then, click once again.',CROP_THUMBS_LANG),
				'cropDisabled' => __('Cropping is disabled for this post-type.',CROP_THUMBS_LANG),
				'waiting' => __('Please wait until the images are cropped.',CROP_THUMBS_LANG),
				'rawImage' => __('Raw',CROP_THUMBS_LANG),
				'pixel' => __('pixel',CROP_THUMBS_LANG),
				'instructions_header' => __('Quick Instructions',CROP_THUMBS_LANG),
				'instructions_step_1' => __('Step 1: Choose an image from the right.',CROP_THUMBS_LANG),
				'instructions_step_2' => __('Step 2: Use your mouse to change the size of the rectangle on the image above.',CROP_THUMBS_LANG),
				'instructions_step_3' => __('Step 3: Click on "Save Crop".',CROP_THUMBS_LANG),
				'label_crop' => __('Save Crop',CROP_THUMBS_LANG),
				'label_same_ratio' => __('Crop all images with same ratio at once',CROP_THUMBS_LANG),
				'label_deselect_all' => __('deselect all',CROP_THUMBS_LANG),
				'dimensions' => __('Dimensions:',CROP_THUMBS_LANG),
				'ratio' => __('Ratio:',CROP_THUMBS_LANG),
				'cropped' => __('cropped',CROP_THUMBS_LANG),
				'lowResWarning' => __('Original image size too small for good crop quality!',CROP_THUMBS_LANG),
			),
			'nonce' => wp_create_nonce($cptSettings->getNonceBase())
		);
		
		//simple validation
		if(empty($_REQUEST['imageId'])) {
			throw new InvalidArgumentException('Missing Parameter "imageId".');
		}
		
		$result['imageObj'] = get_post(intval($_REQUEST['imageId']));
		if(empty($result['imageObj']) || $result['imageObj']->post_type!=='attachment') {
			throw new InvalidArgumentException('Image with ID:'.intval($_REQUEST['imageId']).' could not be found');
		}

		if(!empty($_REQUEST['posttype']) && post_type_exists($_REQUEST['posttype'])) {
			$result['postTypeFilter'] = $_REQUEST['posttype'];
		}
		
		$result['fullSizeImage'] = $this->getUncroppedImageData($result['imageObj']->ID, 'full');
		$result['largeSizeImage'] = $this->getUncroppedImageData($result['imageObj']->ID, 'large');
		$result['hiddenOnPostType'] = self::shouldBeHiddenOnPostType($options,$current_parent_post_type);
		
		if(!$result['hiddenOnPostType']) {
			
			foreach($result['imageSizes'] as $key => $imageSize) {
				
				if(empty($imageSize['crop']) || $imageSize['width']<=0 || $imageSize['height']<=0) {
					//we do not need uncropped image sizes
					unset($result['imageSizes'][$key]);
					continue;//to the next entry
				}
				
				
				//DEFINE RATIO AND GCD
				//DEFAULT RATIO - defined by the defined image-size
				$ratioData = $this->calculateRatioData($imageSize['width'],$imageSize['height']);
				
				
				//DYNAMIC RATIO
				//the dynamic ratio is defined by the original image size and fix width OR height
				//@eee https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
				if($imageSize['width'] === 9999) {
					//if you define width with 9999 - it crops for the exect defined height but the full width
					$ratioData = $this->calculateRatioData($result['fullSizeImage']['width'], $imageSize['height']);
				} elseif($imageSize['height'] === 9999) {
					//if you define height with 9999 - it crops for the exect defined width but the full height
					$ratioData = $this->calculateRatioData($imageSize['width'], $result['fullSizeImage']['height']);
				}
				
				
				$img_data = wp_get_attachment_image_src($result['imageObj']->ID, $imageSize['name']);
				$jsonDataValues = array(
					'name' => $imageSize['name'],
					'nameLabel' => $imageSize['name'],//if you want to change the label of this image-size
					'url' => $img_data[0],
					'width' => $imageSize['width'],
					'height' => $imageSize['height'],
					'gcd' => $ratioData['gcd'],
					'ratio' => $ratioData['ratio'],
					'printRatio' => apply_filters('crop_thumbnails_editor_printratio', $ratioData['printRatio'], $imageSize['name']),
					'hideByPostType' => self::shouldSizeBeHidden($options,$imageSize,$result['postTypeFilter']),
					'crop' => true//legacy
				);
				
				$result['imageSizes'][$key] = apply_filters('crop_thumbnails_editor_jsonDataValues', $jsonDataValues);
				
			}//END froeach
		}
		
		if(is_array($result['imageSizes'])) $result['imageSizes'] = array_values($result['imageSizes']);
		return $result;
	}
	
	private function getUncroppedImageData($ID, $imageSize = 'full') {
		$orig_img = wp_get_attachment_image_src($ID, $imageSize);
		$orig_ima_gcd = $this->gcd($orig_img[1], $orig_img[2]);
		$result = array(
			'url' => $orig_img[0],
			'width' => $orig_img[1],
			'height' => $orig_img[2],
			'gcd' => $orig_ima_gcd,
			'ratio' => ($orig_img[1]/$orig_ima_gcd) / ($orig_img[2]/$orig_ima_gcd),
			'print_ratio' => ($orig_img[1]/$orig_ima_gcd).':'.($orig_img[2]/$orig_ima_gcd),
			'image_size' => $imageSize
		);
		return $result;
	}
	
	private function calculateRatioData($width,$height) {
		$gcd = $this->gcd($width,$height);
		$result = array(
			'gcd' => $gcd,
			'ratio' => ($width/$gcd) / ($height/$gcd),
			'printRatio' => $width/$gcd.':'.$height/$gcd
		);
		return $result;
	}

	private static function shouldBeHiddenOnPostType($options,$post_type) {
		if(empty($post_type)) {
			return false;
		}
		if(empty($options['hide_post_type'][$post_type])) {
			return false;
		}
		return true;
	}

	/**
	 * Check wether or not the image_size should be hidden for this post_type
	 * @param array options array
	 * @param array the image-size (i.e. post-thumbnail, ...)
	 * @param string name post-type (i.e. post, page, ...)
	 * @return boolean true if Image-size should be hidden
	 */
	private static function shouldSizeBeHidden($options, $img_size, $post_type='') {
		$_return = false;
		if(!empty($post_type)) {
			//we are NOT in the mediathek

			//-if hide_size
			if(!empty($options['hide_size'][$post_type][ $img_size['name'] ])) {
				$_return = true;
			}

			//if not a crop-size and allow_non_cropped
			if(empty($img_size['crop']) && empty($options['allow_non_cropped'])) {
				$_return = true;
			}
		} else {
			//we are in the mediathek

			//-if not a crop-size and allow_non_cropped
			if(empty($img_size['crop']) && empty($options['allow_non_cropped'])) {
				$_return = true;
			}
		}
		return $_return;
	}


	private static function isUserPermitted() {
		$return = false;
		if(current_user_can('upload_files')) {
			$return = true;
		}
		//TODO maybe add noence (is it needed? there are no file- or db-operations)
		return $return;
	}


	/**
	 * Greatest cummon divisor
	 */
	private function gcd($a, $b) {
		if(function_exists('gmp_gcd')) {
			$gcd = gmp_strval(gmp_gcd($a,$b));
			return ($gcd);
		} else {
			$gcd = self::my_gcd($a,$b);
			return $gcd;
		}
	}

	private static function my_gcd($a, $b) {
		$b = ( $a == 0 )? 0 : $b;
		return ( $a % $b )? self::my_gcd($b, abs($a - $b)) : $b;
	}
}

$cpte = new CropPostThumbnailsEditor();
