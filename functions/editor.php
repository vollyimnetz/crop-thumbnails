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
		global $content_width;//include nasty content_width
		$content_width = 9999;//override the idioty
		
		$options = $cptSettings->getOptions();
		$result = array(
			'options' => $options,
			'sourceImageId' => null,
			'sourceImage' => array(
				'full' => null,
				'large' => null,
				'medium_large' => null,
			),
			'sourceImageMeta' => null,
			'postTypeFilter' => null,
			'imageSizes' => array_values($cptSettings->getImageSizes()),
			'lang' => array(
				'warningOriginalToSmall' => __('Warning: the original image is too small to be cropped in good quality with this thumbnail size.','cpt_lang'),
				'cropDisabled' => __('Cropping is disabled for this post-type.','cpt_lang'),
				'waiting' => __('Please wait until the images are cropped.','cpt_lang'),
				'rawImage' => __('Raw','cpt_lang'),
				'pixel' => __('pixel','cpt_lang'),
				'instructions_header' => __('Quick Instructions','cpt_lang'),
				'instructions_step_1' => __('Step 1: Choose an image from the right.','cpt_lang'),
				'instructions_step_2' => __('Step 2: Use your mouse to change the size of the rectangle on the image above.','cpt_lang'),
				'instructions_step_3' => __('Step 3: Click on "Save Crop".','cpt_lang'),
				'label_crop' => __('Save Crop','cpt_lang'),
				'label_same_ratio' => __('Crop all images with same ratio at once','cpt_lang'),
				'label_deselect_all' => __('deselect all','cpt_lang'),
				'dimensions' => __('Dimensions:','cpt_lang'),
				'ratio' => __('Ratio:','cpt_lang'),
				'cropped' => __('cropped','cpt_lang'),
				'lowResWarning' => __('Original image size too small for good crop quality!','cpt_lang'),
				'message_image_orientation' => __('This image has an image orientation value in its exif-metadata. Be aware that this may result in rotatated or mirrored images on safari ipad / iphone.','cpt_lang')
			),
			'nonce' => wp_create_nonce($cptSettings->getNonceBase())
		);
		
		//simple validation
		if(empty($_REQUEST['imageId'])) {
			throw new InvalidArgumentException('Missing Parameter "imageId".');
		}
		
		$imagePostObj = get_post(intval($_REQUEST['imageId']));
		if(empty($imagePostObj) || $imagePostObj->post_type!=='attachment') {
			throw new InvalidArgumentException('Image with ID:'.intval($_REQUEST['imageId']).' could not be found');
		}
		$result['sourceImageId'] = $imagePostObj->ID;

		if(!empty($_REQUEST['posttype']) && post_type_exists($_REQUEST['posttype'])) {
			$result['postTypeFilter'] = $_REQUEST['posttype'];
		}
		
		$result['sourceImage']['full'] = $this->getUncroppedImageData($imagePostObj->ID, 'full');
		$result['sourceImage']['large'] = $this->getUncroppedImageData($imagePostObj->ID, 'large');
		$result['sourceImage']['medium_large'] = $this->getUncroppedImageData($imagePostObj->ID, 'medium_large');
		
		//image meta data
		$meta_raw = wp_get_attachment_metadata($imagePostObj->ID);
		if(!empty($meta_raw['image_meta'])) {
			$result['sourceImageMeta'] = $meta_raw['image_meta'];
		}
		
		$result['hiddenOnPostType'] = self::shouldBeHiddenOnPostType($options,$current_parent_post_type);
		
		if(!$result['hiddenOnPostType']) {
			
			foreach($result['imageSizes'] as $key => $imageSize) {
				
				if(empty($imageSize['crop']) || $imageSize['width']<0 || $imageSize['height']<0) {
					//we do not need uncropped image sizes
					unset($result['imageSizes'][$key]);
					continue;//to the next entry
				}
				
				//DEFINE RATIO AND GCD
				if($imageSize['width'] ===0 || $imageSize['height']===0) {
					$ratioData = $this->calculateRatioData($result['sourceImage']['full']['width'],$result['sourceImage']['full']['height']);
				} else {
					//DEFAULT RATIO - defined by the defined image-size
					$ratioData = $this->calculateRatioData($imageSize['width'],$imageSize['height']);
				}
				
				
				
				
				//DYNAMIC RATIO
				//the dynamic ratio is defined by the original image size and fix width OR height
				//@eee https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
				if($imageSize['width'] === 9999) {
					//if you define width with 9999 - it crops for the exact defined height but the full width
					$ratioData = $this->calculateRatioData($result['sourceImage']['full']['width'], $imageSize['height']);
				} elseif($imageSize['height'] === 9999) {
					//if you define height with 9999 - it crops for the exect defined width but the full height
					$ratioData = $this->calculateRatioData($imageSize['width'], $result['sourceImage']['full']['height']);
				}
				
				
				$img_data = wp_get_attachment_image_src($imagePostObj->ID, $imageSize['name']);
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
			'printRatio' => ($orig_img[1]/$orig_ima_gcd).':'.($orig_img[2]/$orig_ima_gcd),
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
