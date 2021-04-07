<?php
class CropThumbnailsSettingsScreen {
	protected static $uniqeSettingsId = 'cpt-settings';
	protected static $cssPrefix = 'cpt_settings_';

	public function __construct() {
		add_action('admin_menu', [$this,'addOptionsPage']);
		if(is_admin()) {
			add_filter('plugin_action_links', [$this,'addSettingsLinkToPluginPage'], 10, 2);
			add_action('admin_head', [$this,'optionsPageStyle']);
			
			//needed for quick-test
			add_action( 'wp_ajax_ctppluginquicktest', [&$this, 'ajax_callback_admin_quicktest'] );
		}
	}

	public function optionsPageStyle() {
		if(!empty($_REQUEST['page']) && $_REQUEST['page']==='page-cpt') {
			wp_enqueue_style('crop-thumbnails-options-style', plugins_url('app/css/app.css', __DIR__), [], CROP_THUMBNAILS_VERSION);
			wp_enqueue_script('vue', plugins_url('app/js/chunk-vendors.js', __DIR__), [], CROP_THUMBNAILS_VERSION);
			wp_enqueue_script('crop-thumbnails-options-js', plugins_url('app/js/app.js', __DIR__ ), ['vue'], CROP_THUMBNAILS_VERSION);
		}
	}

	public function addSettingsLinkToPluginPage($links, $file) {
		if ($file === 'crop-thumbnails/crop-thumbnails.php'){
			$settings_link = '<a href="options-general.php?page=page-cpt" title="">'.esc_html__('Settings','crop-thumbnails').'</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	public function addOptionsPage() {
		add_options_page(esc_html__('Crop Post Thumbnail Page','crop-thumbnails'), 'Crop-Thumbnails', 'manage_options', 'page-cpt', [$this,'optionsPage']);
		add_action('admin_init', [$this,'settingsInitialisation']);
	}

	public function optionsPage() { ?>
		<div class="wrap cropThumbnailSettings">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2>Crop-Thumbnails <?php esc_attr_e('Settings','crop-thumbnails'); ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields( self::$uniqeSettingsId ); ?>
				
				<?php do_settings_sections('page1'); ?>
				
				<div class="<?php echo self::$cssPrefix ?>submit">
					<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes','crop-thumbnails'); ?>" class="button-primary" />
				</div>
			</form>

			<div class="<?php echo self::$cssPrefix; ?>paypal">
				<h3><?php esc_html_e('Support the plugin author','crop-thumbnails') ?></h3>
				<p><?php esc_html_e('You can support the plugin author (and let him know you love this plugin) by donating via Paypal. Thanks a lot!','crop-thumbnails'); ?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_donations">
					<input type="hidden" name="business" value="volkmar.kantor@gmx.de">
					<input type="hidden" name="lc" value="DE">
					<input type="hidden" name="item_name" value="Volkmar Kantor - totalmedial.de">
					<input type="hidden" name="item_number" value="crop-thumbnails">
					<input type="hidden" name="no_note" value="0">
					<input type="hidden" name="currency_code" value="EUR">
					<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
					<input type="image" src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal.">
					<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1">
				</form>
			</div>
		</div>
		<?php
	}

	public function settingsInitialisation(){
		register_setting( self::$uniqeSettingsId, $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey(), [$this,'validateSettings'] );

		$_sectionID = 'choose_sizes_section';
		add_settings_section($_sectionID, esc_html__('Sizes and Post Types','crop-thumbnails'), [$this,'sectionDescriptionChooseSizes'], 'page1');

		$_sectionID = 'userPermission';
		add_settings_section($_sectionID, esc_html__('User Permission','crop-thumbnails'), [$this,'emptySectionDescription'], 'page1');
		$_tmpID = 'user_permission_only_on_edit_files';
		add_settings_field($_tmpID, esc_html__('When active, only users who are able to edit files can crop thumbnails. Otherwise (default), any user who can upload files can also crop thumbnails.','crop-thumbnails'), 	[$this,'callback_'.$_tmpID], 'page1', $_sectionID, ['label_for' => self::$cssPrefix.$_tmpID ]);

		$_sectionID = 'quick_test';
		add_settings_section($_sectionID, esc_html__('Plugin Test','crop-thumbnails'), [$this,'sectionDescriptionTest'], 'page1');
		
		$_sectionID = 'developer';
		add_settings_section($_sectionID, esc_html__('Developer Settings','crop-thumbnails'), [$this,'emptySectionDescription'], 'page1');
		$_tmpID = 'debug_js';
		add_settings_field($_tmpID, esc_html__('Enable JS-Debug.','crop-thumbnails'), [$this,'callback_'.$_tmpID], 'page1', $_sectionID, ['label_for' => self::$cssPrefix.$_tmpID] );
		$_tmpID = 'debug_data';
		add_settings_field($_tmpID, esc_html__('Enable Data-Debug.','crop-thumbnails'), [$this,'callback_'.$_tmpID], 'page1', $_sectionID, ['label_for' => self::$cssPrefix.$_tmpID] );
	}

	protected function vueSettingsScreen() {
		$settings = [
			'options' => $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions(),
			'post_types' => $GLOBALS['CROP_THUMBNAILS_HELPER']->getPostTypes(),
			'image_sizes' => $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes(),
			'lang' => [
				'choose_image_sizes' => esc_js(__('Choose the image sizes you do not want to show, if the user uses the button below the featured image box.','crop-thumbnails')),
				'hide_on_post_type' => esc_js(__('Hide Crop-Thumbnails button below the featured image?','crop-thumbnails'))
			]
		];
		
		?>
		<div class="<?php echo self::$cssPrefix ?>submit">
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes','crop-thumbnails'); ?>" class="button-primary" />
		</div>

		<div id="<?php echo self::$cssPrefix ?>settingsscreen">
			<cpt-settingsscreen settings="<?php echo esc_attr(json_encode($settings)) ?>"></cpt-settingsscreen>
		</div>

		<div class="<?php echo self::$cssPrefix ?>submit">
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes','crop-thumbnails'); ?>" class="button-primary" />
		</div>	
		<?php
	}

	public function sectionDescriptionChooseSizes() {?>
		<p>
			<?php esc_html_e('Crop-Thumbnails is designed to make cropping images easy. For some post types, not all crop sizes are needed, but the plugin will automatically create all the crop sizes. Here you can select which crop sizes are available in the cropping interface for each post type..','crop-thumbnails') ?>
			<br /><strong><?php esc_html_e('Crop-Thumbnails will only show cropped images. Sizes with no crop will always be hidden.','crop-thumbnails'); ?></strong>
		</p>
		<?php
		$this->vueSettingsScreen();
	}

	public function emptySectionDescription() {/*empty*/ }


	
	public function callback_user_permission_only_on_edit_files() {
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$_id = 'user_permission_only_on_edit_files';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey().'['.$_id.']" id="'.self::$cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false) . ' />';
		?>
		<div class="<?php echo self::$cssPrefix ?>submit">
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes','crop-thumbnails'); ?>" class="button-primary" />
		</div>
		<?php
	}

	public function callback_debug_js() {
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$_id = 'debug_js';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey().'['.$_id.']" id="'.self::$cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false) . ' />';
	}

	public function callback_debug_data() {
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$_id = 'debug_data';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey().'['.$_id.']" id="'.self::$cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false ) . ' />';
	}

	public function validateSettings($input) {
		$sizes = $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes();

		$post_types = $GLOBALS['CROP_THUMBNAILS_HELPER']->getPostTypes();

		$storeInDb = [];
		//check input[hide_post_type] --> are the post_types real there
		if(!empty($input['hide_post_type'])) {
			foreach($input['hide_post_type'] as $_post_type_name=>$value) {
				if(isset($post_types[$_post_type_name])) {
					$storeInDb['hide_post_type'][$_post_type_name] = '1';
				}
			}
		}


		//check $input[sizes] --> are post_types correct, are sizes real there
		if(!empty($input['hide_size'])) {
			foreach($input['hide_size'] as $_post_type_name=>$size_type) {
				if(isset($post_types[$_post_type_name])) {
					foreach($size_type as $_size_name=>$value) {
						if(isset($sizes[$_size_name])) {
							$storeInDb['hide_size'][$_post_type_name][$_size_name] = '1';
						}
					}
				}
			}
		}

		$_tmpID = 'user_permission_only_on_edit_files';
		if(!empty($input[$_tmpID])) {
			$storeInDb[$_tmpID] = 1;
		}

		/* Advanced Section */
		$_tmpID = 'debug_js';
		if(!empty($input[$_tmpID])) {
			$storeInDb[$_tmpID] = 1;
		}

		$_tmpID = 'debug_data';
		if(!empty($input[$_tmpID])) {
			$storeInDb[$_tmpID] = 1;
		}

		return $storeInDb;
	}
	
	public function sectionDescriptionTest() {?>
		<button type="button" class="button-secondary cpt_quicktest">Do plugin quick-test.</button>
		
		<script>
		jQuery(document).ready(function($) {
			var currentlyProcessing = false;
			
			
			$('button.cpt_quicktest').click(function(e) {
				e.preventDefault();
				
				if(!currentlyProcessing) {
					currentlyProcessing = true;
					var button = $(this);
					$('#cpt_quicktest').remove();
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'ctppluginquicktest',
							security: '<?php echo wp_create_nonce( "cpt_quicktest-ajax-nonce" );//only for quicktest ?>'
						},
						success: function(responseData){ 
							var output = '<div id="cpt_quicktest">'+responseData+'</div>';
							button.after(output);
							currentlyProcessing = false;
						},
						error: function(responseData) {
							var output = '<div id="cpt_quicktest"><strong class="fails">fail</strong> Failure processing the test - have a look on your server logs.</div>';
							button.after(output);
							currentlyProcessing = false;
						}
					});
				}
			});
		});
		</script>
		<?php
	}


	public function ajax_callback_admin_quicktest() {
		//security
		if(!current_user_can('manage_options')) die('forbidden');
		check_ajax_referer('cpt_quicktest-ajax-nonce','security');//only for quicktest
		
		$report = [];
		$doDeleteAttachement = false;
		$doDeleteTempFile = false;
		$attachmentId = -1;
		
		$sourceFile = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'test_image.jpg';
		$tempFile = $GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir().DIRECTORY_SEPARATOR.'testfile.jpg';
		try {
			$report[] = '<strong class="info">info</strong> Crop-Thumbnails '.CROP_THUMBNAILS_VERSION;
			$report[] = '<strong class="info">info</strong> PHP '.phpversion();
			$report[] = '<strong class="info">info</strong> PHP memory limit '.ini_get('memory_limit');
			$report[] = '<strong class="info">info</strong> '._wp_image_editor_choose(['mime_type' => 'image/jpeg']).' <small>(choosed Wordpress imageeditor class for jpg)</small>';
			
			//check if tmp-folder can be generated
			if(is_dir($GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir())) {
				$report[] = '<strong class="success">success</strong> Temporary directory exists';
			} else {
				if (!mkdir($GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir())) {
					throw new \Exception('<strong class="fails">fail</strong> Creating the temporary directory ('.esc_attr($GLOBALS['CROP_THUMBNAILS_HELPER']->getUploadDir()).') | is the upload-directory writable with PHP?');
				} else {
					$report[] = '<strong class="success">success</strong> Temporary directory could be created';
				}
			}
			
			//creating the testfile in temporary directory
			if(!@copy($sourceFile,$tempFile)) {
				throw new \Exception('<strong class="fails">fail</strong> Copy testfile to temporary directory | is the tmp-directory writable with PHP?');
			} else {
				$report[] = '<strong class="success">success</strong> Copy testfile to temporary directory';
				$doDeleteTempFile = true;
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
			$doDeleteTempFile = false;//is be deleted automatically
			if ( is_wp_error( $attachmentId ) ) {
				throw new \Exception('<strong class="fails">fail</strong> Adding testfile to media-library ('.$attachmentId->get_error_message().') | is the upload-directory writable with PHP?');
			} else {
				$report[] = '<strong class="success">success</strong> Testfile was successfully added to media-library. (ID:'.$attachmentId.')';
				$doDeleteAttachement = true;
			}
			
			
			//try to crop with the same function as the plugin does
			$cropResult = wp_crop_image(    // * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
				$attachmentId,	            // * @param string|int $src The source file or Attachment ID.
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
				throw new \Exception('<strong class="fails">fail</strong> Cropping the file ('.$cropResult->get_error_message().')');
			} else {
				$report[] = '<strong class="success">success</strong> Cropping the file';
				$doDeleteTempFile = true;
				$doDeleteAttachement = true;
			}
			
			
			//check if the dimensions are correct
			$fileDimensions = getimagesize($tempFile);
			if(!empty($fileDimensions[0]) && !empty($fileDimensions[1]) && !empty($fileDimensions['mime'])) {
				$_checkDimensionsOk = true;
				if($fileDimensions[0]!==200 || $fileDimensions[1]!==25) {
					$_checkDimensionsOk = false;
					$report[] = '<strong class="fails">fail</strong> Cropped image dimensions are wrong.';
				}
				if($fileDimensions['mime']!=='image/jpeg') {
					$_checkDimensionsOk = false;
					$report[] = '<strong class="fails">fail</strong> Cropped image dimensions mime-type is wrong.';
				}
				
				if($_checkDimensionsOk) {
					$report[] = '<strong class="success">success</strong> Cropped image dimensions are correct.';
				}
			} else {
				$report[] = '<strong class="fails">fail</strong> Problem with getting the image dimensions of the cropped file.';
			}
			
			
		} catch(\Exception $e) {
			$report[] = $e->getMessage();
		}
		
		
		//DO CLEANUP
		
		//delete attachement file
		if($doDeleteAttachement && $attachmentId!==-1) {
			if ( false === wp_delete_attachment( $attachmentId ) ) {
				$report[] = '<strong class="fails">fail</strong> Error while deleting test attachment';
			} else {
				$report[] = '<strong class="success">success</strong> Test-attachement successfull deleted (ID:'.$attachmentId.')';
			}
		}
		
		
		//deleting testfile form temporary directory
		if($doDeleteTempFile) {
			if(!@unlink($tempFile)) {
				$report[] = '<strong class="fails">fail</strong> Remove testfile from temporary directory';
			} else {
				$report[] = '<strong class="success">success</strong> Remove testfile from temporary directory';
			}
		}
		
		$report[] = '<strong class="info">info</strong> Tests complete';
		echo implode("<br />", $report);
		exit();
	}
}
$cptSettingsScreen = new CropThumbnailsSettingsScreen();
