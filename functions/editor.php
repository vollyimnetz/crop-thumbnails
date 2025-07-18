<?php
namespace crop_thumbnails;

/**
 * Contains all code inside the croping-window
 */

class CPT_ForbiddenException extends \Exception {}

class CropPostThumbnailsEditor {

	protected $debugOutput = '';

	public static function rest_cropdata() {
		try {
			$cpte = new CropPostThumbnailsEditor();
			return $cpte->getCropData();
		} catch(\InvalidArgumentException $e) {
			return new \WP_REST_Response(CropPostThumbnailsEditor::getErrorData('FAILURE while processing request: '.$e->getMessage()), 400);
		} catch(CPT_ForbiddenException $e) {
			return new \WP_REST_Response(CropPostThumbnailsEditor::getErrorData('ERROR not allowed.'), 403);
		} catch(\Exception $e) {
			return new \WP_REST_Response(CropPostThumbnailsEditor::getErrorData('FAILURE while processing request.'), 400);
		}
	}

	/**
	 * Will return an error-object for the frontend
	 * @param string $errorMsg the provided errormessage
	 * @return array
	 */
	public static function getErrorData($errorMsg) {
		return [
			'lang' => self::getLangArray(),
			'nonce' => wp_create_nonce($GLOBALS['CROP_THUMBNAILS_HELPER']->getNonceBase()),
			'error' => $errorMsg
		];
	}

	/**
	 * Fix html-encoded language strings
	 * Note: abstraction to keep the code DRY
	 * @param string $msg the language string to fix
	 * @return string
	 */
	protected static function fixJsLangStrings($msg) {
		return str_replace('&quot;','"',esc_js($msg));
	}

	/**
	 * Returns the lang-array needed for the js-app to work
	 * @return Array
	 */
	protected static function getLangArray() {
		return [
			'warningOriginalToSmall' => self::fixJsLangStrings(__('Warning: the original image is too small to be cropped in good quality with this thumbnail size.','crop-thumbnails')),
			'cropDisabled' => self::fixJsLangStrings(__('Cropping is disabled for this post-type.','crop-thumbnails')),
			'waiting' => self::fixJsLangStrings(__('Please wait until the images are cropped.','crop-thumbnails')),
			'rawImage' => self::fixJsLangStrings(__('Raw','crop-thumbnails')),
			'pixel' => self::fixJsLangStrings(__('pixel','crop-thumbnails')),
			'instructions_overlay_text' => self::fixJsLangStrings(__('Choose an image size.','crop-thumbnails')),
			'instructions_header' => self::fixJsLangStrings(__('Quick Instructions','crop-thumbnails')),
			'instructions_step_1' => self::fixJsLangStrings(__('Step 1: Choose an image-size from the list.','crop-thumbnails')),
			'instructions_step_2' => self::fixJsLangStrings(__('Step 2: Change the selection of the image above.','crop-thumbnails')),
			'instructions_step_3' => self::fixJsLangStrings(__('Step 3: Click on "Save Crop".','crop-thumbnails')),
			'label_crop' => self::fixJsLangStrings(__('Save Crop','crop-thumbnails')),
			'label_same_ratio_mode' => self::fixJsLangStrings(__('Images with same ratio','crop-thumbnails')),
			'label_same_ratio_mode_nothing' => self::fixJsLangStrings(__('Do nothing','crop-thumbnails')),
			'label_same_ratio_mode_select' => self::fixJsLangStrings(__('Select together','crop-thumbnails')),
			'label_same_ratio_mode_group' => self::fixJsLangStrings(__('Group together','crop-thumbnails')),
			'label_deselect_all' => self::fixJsLangStrings(__('deselect all','crop-thumbnails')),
			'label_large_handles' => self::fixJsLangStrings(__('use large handles','crop-thumbnails')),
			'dimensions' => self::fixJsLangStrings(__('Dimensions:','crop-thumbnails')),
			'ratio' => self::fixJsLangStrings(__('Ratio:','crop-thumbnails')),
			'cropped' => self::fixJsLangStrings(__('cropped','crop-thumbnails')),
			'lowResWarning' => self::fixJsLangStrings(__('Original image size too small for good crop quality!','crop-thumbnails')),
			'notYetCropped' => self::fixJsLangStrings(__('Not yet cropped by WordPress.','crop-thumbnails')),
			'message_image_orientation' => self::fixJsLangStrings(__('This image has an image orientation value in its exif-metadata. Be aware that this may result in rotatated or mirrored images on safari ipad / iphone.','crop-thumbnails')),
			'script_connection_error' => self::fixJsLangStrings(__('The plugin can not correctly connect to the server.','crop-thumbnails')),
			'noPermission' => self::fixJsLangStrings(__('You are not permitted to crop the thumbnails.','crop-thumbnails')),
			'unknownError' => self::fixJsLangStrings(__('An unknown error occured.','crop-thumbnails')),
			'infoNoImageSizesAvailable' => self::fixJsLangStrings(__('No image sizes for cropping available.','crop-thumbnails')),
			'headline_selected_image_sizes' => self::fixJsLangStrings(__('Selected image sizes','crop-thumbnails')),
		];
	}

	public function getCropData() {
		global $content_width;//include nasty content_width
		$content_width = 9999;//override the idioty

		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$result = [
			'options' => $options,
			'sourceImageId' => null,
			'sourceImage' => [
				'original_image' => null,
				'full' => null,
				'large' => null,
				'medium_large' => null,
			],
			'cropBaseSize' => 'full',
			'sourceImageMeta' => null,
			'postTypeFilter' => null,
			'imageSizes' => array_values($GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes()),
			'lang' => self::getLangArray(),
			'nonce' => wp_create_nonce($GLOBALS['CROP_THUMBNAILS_HELPER']->getNonceBase())
		];

		//simple validation
		if(empty($_REQUEST['imageId'])) {
			throw new \InvalidArgumentException('Missing Parameter "imageId".');
		}


		$imagePostObj = get_post(intval($_REQUEST['imageId']));
		if(empty($imagePostObj) || $imagePostObj->post_type!=='attachment') {
			throw new \InvalidArgumentException('Image with ID:'.intval($_REQUEST['imageId']).' could not be found');
		}
		$result['sourceImageId'] = $imagePostObj->ID;

		if(!CptSaveThumbnail::isUserPermitted( $imagePostObj->ID )) {
			throw new CPT_ForbiddenException();
		}

		if(!empty($_REQUEST['posttype']) && post_type_exists($_REQUEST['posttype'])) {
			$result['postTypeFilter'] = $_REQUEST['posttype'];
		}

		$result['sourceImage']['original_image'] = $this->getUncroppedImageData($imagePostObj->ID, 'original_image');
		$result['sourceImage']['full'] = $this->getUncroppedImageData($imagePostObj->ID, 'full');
		$result['sourceImage']['large'] = $this->getUncroppedImageData($imagePostObj->ID, 'large');
		$result['sourceImage']['medium_large'] = $this->getUncroppedImageData($imagePostObj->ID, 'medium_large');

		//image meta data
		$meta_raw = wp_get_attachment_metadata($imagePostObj->ID);
		if(!empty($meta_raw['image_meta'])) {
			$result['sourceImageMeta'] = $meta_raw['image_meta'];
		}

		$result['hiddenOnPostType'] = self::shouldBeHiddenOnPostType($options, $result['postTypeFilter']);
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

				$img_data = wp_get_attachment_image_src($imagePostObj->ID, $imageSize['id']);
				$jsonDataValues = [
					'name' => $imageSize['id'],
					'nameLabel' => $imageSize['name'],//if you want to change the label of this image-size
					'url' => $img_data[0],
					'width' => $imageSize['width'],
					'height' => $imageSize['height'],
					'gcd' => $ratioData['gcd'],
					'ratio' => $ratioData['ratio'],
					'printRatio' => apply_filters('crop_thumbnails_editor_printratio', $ratioData['printRatio'], $imageSize['id']),
					'hideByPostType' => self::shouldSizeBeHidden($options,$imageSize,$result['postTypeFilter']),
					'crop' => true//legacy
				];

				$result['imageSizes'][$key] = apply_filters('crop_thumbnails_editor_jsonDataValues', $jsonDataValues);

			}//END froeach
		}

		$result['imageSizes'] = apply_filters('crop_thumbnails_crop_data_image_sizes', $result['imageSizes']);

		if(is_array($result['imageSizes'])) $result['imageSizes'] = array_values($result['imageSizes']);

		return apply_filters('crop_thumbnails_crop_data', $result);
	}

	protected function getUncroppedImageData($ID, $imageSize = 'full') {
		$orig_img = wp_get_attachment_image_src($ID, $imageSize);
		if($imageSize === 'original_image') {
			$tmp = wp_getimagesize(wp_get_original_image_path($ID));
			$orig_img = [ wp_get_original_image_url($ID), $tmp[0], $tmp[1], false ];
		}
		$orig_ima_gcd = $this->gcd($orig_img[1], $orig_img[2]);
		$result = [
			'url' => $orig_img[0],
			'width' => $orig_img[1],
			'height' => $orig_img[2],
			'gcd' => $orig_ima_gcd,
			'ratio' => ($orig_img[1]/$orig_ima_gcd) / ($orig_img[2]/$orig_ima_gcd),
			'printRatio' => ($orig_img[1]/$orig_ima_gcd).':'.($orig_img[2]/$orig_ima_gcd),
			'image_size' => $imageSize
		];
		return $result;
	}

	protected function calculateRatioData($width,$height) {
		$gcd = $this->gcd($width,$height);
		$result = [
			'gcd' => $gcd,
			'ratio' => ($width/$gcd) / ($height/$gcd),
			'printRatio' => $width/$gcd.':'.$height/$gcd
		];
		return $result;
	}

	protected static function shouldBeHiddenOnPostType($options,$post_type) {
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
	protected static function shouldSizeBeHidden($options, $img_size, $post_type='') {
		$_return = false;
		if(!empty($post_type)) {
			//we are NOT in the mediathek

			//-if hide_size
			if(!empty($options['hide_size'][$post_type][ $img_size['id'] ])) {
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


	/**
	 * Greatest cummon divisor
	 */
	protected function gcd($a, $b) {
		if(function_exists('gmp_gcd')) {
			$gcd = gmp_strval(gmp_gcd($a,$b));
			return ($gcd);
		} else {
			$gcd = self::my_gcd($a,$b);
			return $gcd;
		}
	}

	protected static function my_gcd($a, $b) {
		$b = ( $a == 0 )? 0 : $b;
		return ( $a % $b )? self::my_gcd($b, abs($a - $b)) : $b;
	}
}
