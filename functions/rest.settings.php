<?php
namespace crop_thumbnails;

/**
 * Rest operations of the settings screen
 */
class RestSettings {
	const OPTIONS_KEY = 'crop-post-thumbs';

	public static function init() {
		add_action( 'rest_api_init', [self::class, 'initRest']);
	}

	public static function initRest() {
		//add endpoints for settings
		register_rest_route( 'crop_thumbnails/v1', 'settings', [
			'methods' => 'GET',
			'callback' => [self::class, 'rest_status'],
			'schema' => null,
			'permission_callback' => [self::class, 'checkRestPermission']
		]);
		register_rest_route( 'crop_thumbnails/v1', 'pluginTest', [
			'methods' => 'POST',
			'callback' => [self::class, 'rest_pluginTest'],
			'schema' => null,
			'permission_callback' => [self::class, 'checkRestPermission']
		]);
		register_rest_route( 'crop_thumbnails/v1', 'settings/postTypes', [
			'methods' => 'POST',
			'callback' => [self::class, 'rest_postTypes'],
			'schema' => null,
			'permission_callback' => [self::class, 'checkRestPermission']
		]);
		register_rest_route( 'crop_thumbnails/v1', 'settings/userPermission', [
			'methods' => 'POST',
			'callback' => [self::class, 'rest_userPermission'],
			'schema' => null,
			'permission_callback' => [self::class, 'checkRestPermission']
		]);
		register_rest_route( 'crop_thumbnails/v1', 'settings/developerSettings', [
			'methods' => 'POST',
			'callback' => [self::class, 'rest_developerSettings'],
			'schema' => null,
			'permission_callback' => [self::class, 'checkRestPermission']
		]);
		register_rest_route( 'crop_thumbnails/v1', 'settings/resetSettings', [
			'methods' => 'POST',
			'callback' => [self::class, 'rest_resetSettings'],
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

	public static function getOptions() {
		return get_option(self::OPTIONS_KEY);
	}

	public static function setOptions($options) {
		update_option(self::OPTIONS_KEY, $options, false);
	}

	public static function deleteOptions() {
		delete_option(self::OPTIONS_KEY);
	}

	public static function rest_resetSettings() {
		self::deleteOptions();
	}

	public static function rest_status() {
		$options = self::getOptions();
		$result = [
			'options' => $options,
			'post_types' => $GLOBALS['CROP_THUMBNAILS_HELPER']->getPostTypes(),
			'image_sizes' => $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes(),
			'lang' => [
				'general' => [
					'save_changes' => esc_js(__('Save Changes','crop-thumbnails')),
					'successful_saved' => esc_js(__('Successful saved','crop-thumbnails')),
					'nav_post_types' => esc_js(__('Sizes and Post Types','crop-thumbnails')),
					'nav_plugin_test' => esc_js(__('Plugin Test','crop-thumbnails')),
					'nav_developer_settings' => esc_js(__('Developer Settings','crop-thumbnails')),
					'nav_user_permissions' => esc_js(__('User Settings','crop-thumbnails')),
				],
				'user_settings' => [
					'nav_user_permissions' => esc_js(__('User Permission','crop-thumbnails')),
					'text_user_permissions' => esc_js(__('When active, only users who are able to edit files can crop thumbnails. Otherwise (default), any user who can upload files can also crop thumbnails.','crop-thumbnails')),
					'nav_same_ratio_mode' => esc_js(__('Grouping with same aspect ration','crop-thumbnails')),
					'text_same_ratio_mode' => esc_js(__('You may specify that users do not have a selection option for “Grouping with the same aspect ratio”. Select the type of grouping that should apply to all users here.','crop-thumbnails')),
					'label_same_ratio_mode' => esc_js(__('Images with same ratio','crop-thumbnails')),
					'label_same_ratio_mode_default' => esc_js(__('Let user decide (default)','crop-thumbnails')),
					'label_same_ratio_mode_select' => esc_js(__('Select together','crop-thumbnails')),
					'label_same_ratio_mode_group' => esc_js(__('Group together','crop-thumbnails')),
				],
				'posttype_settings' => [
					'intro_1' => esc_js(__('Crop-Thumbnails is designed to make cropping images easy. For some post types, not all crop sizes are needed, but the plugin will automatically create all the crop sizes. Here you can select which crop sizes are available in the cropping interface for each post type.','crop-thumbnails')),
					'intro_2' => esc_js(__('Crop-Thumbnails will only show cropped images. Sizes with no crop will always be hidden.','crop-thumbnails')),
					'choose_image_sizes' => esc_js(__('Choose the image sizes you do not want to show, if the user uses the button below the featured image box.','crop-thumbnails')),
					'hide_on_post_type' => esc_js(__('Hide Crop-Thumbnails button below the featured image?','crop-thumbnails'))
				],
				'developer_settings' => [
					'enable_debug_js' => esc_js(__('Enable JS-Debug.','crop-thumbnails')),
					'enable_debug_data' => esc_js(__('Enable Data-Debug.','crop-thumbnails')),
					'include_js_on_all_admin_pages' => esc_js(__('Include plugins javascript on all admin pages (normally not needed, but usefull if you want to add the functionality also i.e. on categories).','crop-thumbnails')),
					'reset_settings' => esc_js(__('Reset all plugin settings','crop-thumbnails')),
					'confirm_settings_reset' => esc_js(__('Are you sure, you want to reset all plugin settings?','crop-thumbnails')),
				],
				'paypal_info' => [
					'headline' => esc_js(__('Support the plugin author','crop-thumbnails')),
					'text' => esc_js(__('You can support the plugin author (and let him know you love this plugin) by donating via Paypal. Thanks a lot!','crop-thumbnails')),
				]
			]
		];
		return $result;
	}

	public static function rest_postTypes(\WP_REST_Request $request) {
		try {
			$postTypes = $request->get_params();
			$newOptions = self::getOptions();

			$newOptions['hide_post_type'] = [];
			$newOptions['hide_size'] = [];
			if(!empty($postTypes)) foreach($postTypes as $postType) {
				if($postType['hidden']===true) $newOptions['hide_post_type'][ $postType['name'] ] = "1";

				if(!empty($postType['imageSizes'])) foreach($postType['imageSizes'] as $postImageSizes) {
					if($postImageSizes['hidden']===true) {
						if(empty( $newOptions['hide_size'][ $postType['name'] ] )) $newOptions['hide_size'][ $postType['name'] ] = [];
						$newOptions['hide_size'][ $postType['name'] ][ $postImageSizes['id'] ] = "1";
					}
				}
			}
			self::setOptions($newOptions);

			return ['input' => $postTypes, 'newOptions' => $newOptions];
		} catch (\Throwable $th) {
			return new \WP_REST_Response(['error' => $th->getMessage()], 423);// something went wrong
		}
	}

	public static function rest_developerSettings(\WP_REST_Request $request) {
		try {
			$input = $request->get_params();
			$newOptions = self::getOptions();

			unset($newOptions['debug_js']);
			unset($newOptions['debug_data']);
			unset($newOptions['include_js_on_all_admin_pages']);

			if($input['enable_debug_js']) $newOptions['debug_js'] = 1;
			if($input['enable_debug_data']) $newOptions['debug_data'] = 1;
			if($input['include_js_on_all_admin_pages']) $newOptions['include_js_on_all_admin_pages'] = 1;

			self::setOptions($newOptions);

			return ['input' => $input, 'newOptions' => $newOptions];
		} catch (\Throwable $th) {
			return new \WP_REST_Response(['error' => $th->getMessage()], 423);// something went wrong
		}
	}

	public static function rest_userPermission(\WP_REST_Request $request) {
		try {
			$input = $request->get_params();
			$newOptions = self::getOptions();

			unset($newOptions['user_permission_only_on_edit_files']);
			unset($newOptions['same_ratio_mode']);

			if($input['user_permission_only_on_edit_files']) $newOptions['user_permission_only_on_edit_files'] = 1;
			if(!empty($input['same_ratio_mode']) && in_array($input['same_ratio_mode'], ['select','group'])) $newOptions['same_ratio_mode'] = $input['same_ratio_mode'];

			self::setOptions($newOptions);

			return ['input' => $input, 'newOptions' => $newOptions];
		} catch (\Throwable $th) {
			return new \WP_REST_Response(['error' => $th->getMessage()], 423);// something went wrong
		}
	}

	public static function rest_pluginTest() {
		// These files need to be included as dependencies
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$report = [];
		$doDeleteAttachement = false;
		$attachmentId = -1;
		$testComplete = false;

		$sourceFile = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'test_image.jpg';
		$tempFile = $GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir().DIRECTORY_SEPARATOR.'testfile.jpg';
		try {
			//check if tmp-folder can be generated
			if(is_dir($GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir())) {
				$report[] = '<strong class="success">SUCCESS</strong> Temporary directory exists';
			} else {
				if (!mkdir($GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir())) {
					throw new \Exception('<strong class="fails">FAIL</strong> Creating the temporary directory ('.esc_attr($GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir()).') | is the upload-directory writable with PHP?');
				} else {
					$report[] = '<strong class="success">SUCCESS</strong> Temporary directory could be created';
				}
			}

			//creating the testfile in temporary directory
			if(!@copy($sourceFile, $tempFile)) {
				throw new \Exception('<strong class="fails">FAIL</strong> Copy testfile to temporary directory | is the tmp-directory writable with PHP? ['.$sourceFile.' --> '.$tempFile.']');
			} else {
				$report[] = '<strong class="success">SUCCESS</strong> Copy testfile to temporary directory';
			}


			//try to upload the file
			$_FILES['cpt_quicktest'] = [
				'name' => 'test_image.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => $tempFile,
				'error' => 0,
				'size' => 102610
			];
			$attachmentId = media_handle_upload( 'cpt_quicktest', 0, [], ['test_form' => false, 'action'=>'test'] );

			if ( is_wp_error( $attachmentId ) ) {
				throw new \Exception('<strong class="fails">FAIL</strong> Adding testfile to media-library ('.$attachmentId->get_error_message().') | is the upload-directory writable with PHP?');
			} else {
				$report[] = '<strong class="success">SUCCESS</strong> Testfile was successfully added to media-library. (ID:'.$attachmentId.')';
				$doDeleteAttachement = true;
			}


			//try to crop with the same function as the plugin does
			$src = wp_get_original_image_path($attachmentId);
			$cropResult = wp_crop_image(    // * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
				$src,	                    // * @param string|int $src The source file or Attachment ID.
				130,                        // * @param int $src_x The start x position to crop from.
				275,                        // * @param int $src_y The start y position to crop from.
				945,                        // * @param int $src_w The width to crop.
				120,                        // * @param int $src_h The height to crop.
				200,                        // * @param int $dst_w The destination width.
				25,                         // * @param int $dst_h The destination height.
				false,                      // * @param int $src_abs Optional. If the source crop points are absolute.
				$tempFile                   // * @param string $dst_file Optional. The destination file to write to.
			);
			if ( is_wp_error( $cropResult ) ) {
				throw new \Exception('<strong class="fails">FAIL</strong> Cropping the file ('.$cropResult->get_error_message().')');
			} else {
				$report[] = '<strong class="success">SUCCESS</strong> Cropping the file';
				$doDeleteAttachement = true;
			}


			//check if the dimensions are correct
			$fileDimensions = getimagesize($tempFile);
			if(!empty($fileDimensions[0]) && !empty($fileDimensions[1]) && !empty($fileDimensions['mime'])) {
				$_checkDimensionsOk = true;
				if($fileDimensions[0]!==200 || $fileDimensions[1]!==25) {
					$_checkDimensionsOk = false;
					$report[] = '<strong class="fails">FAIL</strong> Cropped image dimensions are wrong.';
				}
				if($fileDimensions['mime']!=='image/jpeg') {
					$_checkDimensionsOk = false;
					$report[] = '<strong class="fails">FAIL</strong> Cropped image dimensions mime-type is wrong.';
				}

				if($_checkDimensionsOk) {
					$report[] = '<strong class="success">SUCCESS</strong> Cropped image dimensions are correct.';
				}
			} else {
				$report[] = '<strong class="fails">FAIL</strong> Problem with getting the image dimensions of the cropped file.';
			}

			//DO CLEANUP

			//delete attachement file
			if($doDeleteAttachement && $attachmentId!==-1) {
				if ( false === wp_delete_attachment( $attachmentId ) ) {
					$report[] = '<strong class="fails">FAIL</strong> Error while deleting test attachment';
				} else {
					$report[] = '<strong class="success">SUCCESS</strong> Test-attachement successfull deleted (ID:'.$attachmentId.')';
				}
			}


			$testComplete = true;
		} catch (\Throwable $th) {
			$report[] = $th->getMessage();
		}

		//deleting testfile form temporary directory
		if(file_exists($tempFile)) {
			if(!@unlink($tempFile)) {
				$report[] = '<strong class="fails">FAIL</strong> Remove testfile from temporary directory';
			} else {
				$report[] = '<strong class="success">SUCCESS</strong> Remove testfile from temporary directory';
			}
		}

		if($testComplete) $report[] = '<strong class="info">INFO</strong> Tests complete';

		self::appendSystemInfo($report);
		return $report;
	}

	private static function appendSystemInfo(&$report) {
		$report[] = '<strong class="info">INFO</strong> ----- System -----';
		$report[] = '<strong class="info">INFO</strong> Crop-Thumbnails '.CROP_THUMBNAILS_VERSION;
		$report[] = '<strong class="info">INFO</strong> PHP '.phpversion();
		$report[] = '<strong class="info">INFO</strong> PHP memory limit '.ini_get('memory_limit');
		$report[] = '<strong class="info">INFO</strong> Server '.(!empty($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : $_ENV['SERVER_SOFTWARE']);
		$report[] = '<strong class="info">INFO</strong> '._wp_image_editor_choose(['mime_type' => 'image/jpeg']).' <small>(choosed Wordpress imageeditor class for jpg)</small>';

		// get all plugins
		$activePlugins = get_option('active_plugins');

		$report[] = '<strong class="info">INFO</strong> ----- Active Plugins -----';
		foreach($activePlugins as $pluginPath) {
			$report[] = '<strong class="info">INFO</strong> - '.$pluginPath;
		}
		$imageSizes = $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes();
		if(!empty($imageSizes)) {
			$report[] = '<strong class="info">INFO</strong> ----- Image Sizes -----';
			foreach($imageSizes as $imageSize) {
				$report[] = '<strong class="info">INFO</strong> <code>'.json_encode($imageSize).'</code>';
			}
		}

	}
}
RestSettings::init();
