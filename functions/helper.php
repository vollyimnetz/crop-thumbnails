<?php

/**
 * Do not use functions of this class directly, instead use
 * $GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir()
 * $GLOBALS['CROP_THUMBNAILS_HELPER']->getPostTypes()
 * $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes()
 * $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey()
 * $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions()
 * $GLOBALS['CROP_THUMBNAILS_HELPER']->getNonceBase()
 * 
 * This class is for overriding settings in php-tests - therefore the functions cant be static :(
 */
class CropThumbnailsHelper {
	protected static $defaultSizes = array('thumbnail','medium','medium_large','large');
	protected static $optionsKey = 'crop-post-thumbs';

	public function getUploadDir() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'].DIRECTORY_SEPARATOR.'tmp';
	}


	/**
	 * get the post types and delete some prebuild post types that we dont need
	 */
	public function getPostTypes() {
		$post_types = get_post_types(array(),'objects');
		unset($post_types['nav_menu_item']);
		unset($post_types['revision']);
		unset($post_types['attachment']);
		return $post_types;
	}


	/**
	 * <pre>
	 * Creates an array of all image sizes.
	 * @return {array} array of all image sizes
	 *                       array[<sizename>]['height'] = int
	 *                       array[<sizename>]['width'] = int
	 *                       array[<sizename>]['crop'] = boolean
	 *                       array[<sizename>]['name'] = string --> readable name if provided in "image_size_names_choose", else sizename
	 *                       array[<sizename>]['id'] = string --> the sizename
	 * </pre>
	 */
	public function getImageSizes() {
		global $_wp_additional_image_sizes;//array with the available image sizes
		$image_size_names = array_flip(get_intermediate_image_sizes());
		foreach($image_size_names as $key=>$value) {
			$image_size_names[$key] = $key;
		}
		
		$tmp_sizes = apply_filters( 'image_size_names_choose', $image_size_names );
		$image_size_names = array_merge($image_size_names,$tmp_sizes);
		
		$sizes = array();
		foreach( $image_size_names as $sizeId=>$theName ) {

			if ( in_array( $sizeId, self::$defaultSizes ) ) {
				$sizes[ $sizeId ]['width']  = intval(get_option( $sizeId . '_size_w' ));
				$sizes[ $sizeId ]['height'] = intval(get_option( $sizeId . '_size_h' ));
				$sizes[ $sizeId ]['crop']   = (bool) get_option( $sizeId . '_crop' );
			} else {
				if(!empty($_wp_additional_image_sizes[ $sizeId ])) {
					$sizes[ $sizeId ] = array(
						'width'  => intval($_wp_additional_image_sizes[ $sizeId ]['width']),
						'height' => intval($_wp_additional_image_sizes[ $sizeId ]['height']),
						'crop'   => (bool) $_wp_additional_image_sizes[ $sizeId ]['crop']
					);
				}
			}
			$sizes[ $sizeId ]['name'] = $theName;
			$sizes[ $sizeId ]['id'] = $sizeId;
		}
		$sizes = apply_filters('crop_thumbnails_image_sizes',$sizes);
		return $sizes;
	}

	public function getOptionsKey() {
		return self::$optionsKey;
	}

	public function getOptions() {
		return get_option(self::$optionsKey);
	}

	public function getNonceBase() {
		return 'crop-post-thumbnails-nonce-base';
	}
}
$GLOBALS['CROP_THUMBNAILS_HELPER'] = new CropThumbnailsHelper();