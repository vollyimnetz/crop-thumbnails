<?php
namespace crop_thumbnails\toolkit;

/**
 * Rest operations of the settings screen
 */
class RestToolkit {
	const OPTIONS_KEY = 'crop-post-thumbs';

	public static function init() {
		add_action( 'rest_api_init', [self::class, 'initRest']);
	}

	public static function initRest() {
		//add endpoint customeranalysis
		register_rest_route( 'crop_thumbnails/v1/toolkit', 'base', [
			'methods' => 'GET',
			'callback' => [self::class, 'rest_status'],
			'schema' => null,
			'permission_callback' => [self::class, 'checkRestPermission']
		]);
	}
	
	public static function checkRestPermission() {
		if(!current_user_can('manage_options')) {
			error_log('Try to access without permission (API).');
			return false;
		}
		return true;
	}

	public static function rest_status() {
		$result = [
			'imageSizes' => $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes(),
			'wp_upload_dir' => wp_upload_dir(),
			'admin_url' => admin_url(),
			'images' => Toolkit::getAllImages(),
			'post_thumbnails' => Toolkit::getAllPostThumbnails(),
		];
		return $result;
	}

}
RestToolkit::init();
