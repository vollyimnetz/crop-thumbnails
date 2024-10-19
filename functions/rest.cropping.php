<?php
namespace crop_thumbnails;

/**
 * Rest operations of the settings screen
 */
class RestCropping {
	const OPTIONS_KEY = 'crop-post-thumbs';

	public static function init() {
		add_action( 'rest_api_init', [self::class, 'initRest']);
	}

	public static function initRest() {
		//add endpoints for cropping
		register_rest_route( 'crop_thumbnails/v1', 'crop', [
			'methods' => 'GET',
			'callback' => [CropPostThumbnailsEditor::class, 'rest_cropdata'],
			'schema' => null,
			'permission_callback' => [CptSaveThumbnail::class, 'checkRestPermission']
		]);
		register_rest_route( 'crop_thumbnails/v1', 'crop', [
			'methods' => 'POST',
			'callback' => [CptSaveThumbnail::class, 'saveThumbnail'],
			'schema' => null,
			'permission_callback' => [CptSaveThumbnail::class, 'checkRestPermission']
		]);
	}
}
RestCropping::init();
