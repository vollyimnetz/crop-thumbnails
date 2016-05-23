<?php
class CropThumbnailsSettings {
	private $uniqeSettingsId = 'cpt-settings';
	private $optionsKey = 'crop-post-thumbs';
	private $cssPrefix = 'cpt_settings_';
	private $defaultSizes = array('thumbnail','medium','medium_large','large');

	function __construct() {
		add_action('admin_menu', array($this,'addOptionsPage'));
		if(is_admin()) {
			add_filter('plugin_action_links', array($this,'addSettingsLinkToPluginPage'), 10, 2);
			add_action('admin_head', array($this,'optionsPageStyle'));
			
			//needed for quick-test
			add_action( 'wp_ajax_ctppluginquicktest', array(&$this, 'ajax_callback_admin_quicktest') );
		}
	}

	function optionsPageStyle() {
		if(!empty($_REQUEST['page']) && $_REQUEST['page']=='page-cpt') {
			wp_enqueue_style('crop-thumbnails-options-style',plugins_url('css/options.css',dirname(__FILE__)));
		}
	}

	function addSettingsLinkToPluginPage($links, $file) {
		if ($file === 'crop-thumbnails/crop-thumbnails.php'){
			$settings_link = '<a href="options-general.php?page=page-cpt" title="">'.__('Settings',CROP_THUMBS_LANG).'</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	function addOptionsPage() {
		add_options_page(__('Crop Post Thumbnail Page',CROP_THUMBS_LANG), 'Crop-Thumbnails', 'manage_options', 'page-cpt', array($this,'optionsPage'));
		add_action('admin_init', array($this,'settingsInitialisation'));
	}

	function optionsPage() { ?>
		<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2>Crop-Thumbnails <?php esc_attr_e('Settings',CROP_THUMBS_LANG); ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields($this->uniqeSettingsId); ?>
				<?php do_settings_sections('page1'); ?>

				<div class="<?php echo $this->cssPrefix ?>submit">
					<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes',CROP_THUMBS_LANG); ?>" class="button-primary" />
				</div>
			</form>

			<div class="<?php echo $this->cssPrefix; ?>paypal">
				<h3><?php _e('Support the plugin author',CROP_THUMBS_LANG) ?></h3>
				<p><?php _e('You can support the plugin author <br />(and let him know you love this plugin) <br />by donating via Paypal. Thanks a lot!',CROP_THUMBS_LANG); ?></p>
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

	function settingsInitialisation(){
		register_setting( $this->uniqeSettingsId, $this->optionsKey, array($this,'validateSettings') );

		$_sectionID = 'choose_sizes_section';
		add_settings_section($_sectionID, __('Sizes and Post Types',CROP_THUMBS_LANG), array($this,'sectionDescriptionChooseSizes'), 'page1');
		add_settings_field('sizes', __('Choose the image size options you want to hide for each post type.',CROP_THUMBS_LANG), array($this,'callback_choose_size'), 'page1', $_sectionID);
		
		$_sectionID = 'quick_test';
		add_settings_section($_sectionID, __('Plugin Test',CROP_THUMBS_LANG), array($this,'sectionDescriptionTest'), 'page1');
		
		$_sectionID = 'developer';
		add_settings_section($_sectionID, __('Developer Settings',CROP_THUMBS_LANG), array($this,'emptySectionDescription'), 'page1');
		$_tmpID = 'debug_js';
		add_settings_field($_tmpID, __('Enable JS-Debug.',CROP_THUMBS_LANG), 	array($this,'callback_'.$_tmpID), 'page1', $_sectionID, array( 'label_for' => $this->cssPrefix.$_tmpID ));
		$_tmpID = 'debug_data';
		add_settings_field($_tmpID, __('Enable Data-Debug.',CROP_THUMBS_LANG), 	array($this,'callback_'.$_tmpID), 'page1', $_sectionID, array( 'label_for' => $this->cssPrefix.$_tmpID ));
	}

	function sectionDescriptionChooseSizes() {?>
		<p>
			<?php _e('Crop-Thumbnails is designed to make cropping images easy. For some post types, not all crop sizes are needed, but the plugin will automatically create all the crop sizes. Here you can select which crop sizes are available in the cropping interface for each post type..',CROP_THUMBS_LANG) ?>
			<br /><strong><?php _e('Crop-Thumbnails will only show cropped images. Sizes with no crop will always be hidden.',CROP_THUMBS_LANG); ?></strong>
		</p>
		<?php
	}

	function emptySectionDescription() {/*empty*/ }
	

	function callback_choose_size() {
		//get all the data
		$options = get_option($this->optionsKey);
		#echo '<pre>'.print_r($options,true).'</pre>';
		$post_types = $this->getPostTypes();
		$image_sizes = $this->getImageSizes();

		//output
		?>
		<ul>
			<?php foreach($post_types as $post_type=>$value) : ?>
			<li>
				<label for="<?php echo $this->cssPrefix.$post_type; ?>">
					<input id="<?php echo $this->cssPrefix.$post_type;?>" type="checkbox" name="<?php echo $this->optionsKey; ?>[hide_post_type][<?php echo $post_type;?>]" value="1" <?php checked(isset($options['hide_post_type'][$post_type]),true); ?> />
					<strong><?php echo $value->labels->name; ?></strong>
				</label>
				<ul style="margin:1em;">
				
				<?php foreach($image_sizes as $thumb_name => $data) :
					$_checked = false;
					if(!empty($options['hide_size']) && is_array($options['hide_size']) && !empty($options['hide_size'][$post_type][$thumb_name])) {
						$_checked = true;
					}
					if($data['crop']=='1') : ?>
						<li>
							<label for="<?php echo $this->cssPrefix.$post_type;?>-<?php echo $thumb_name;?>">
								<input id="<?php echo $this->cssPrefix.$post_type;?>-<?php echo $thumb_name;?>" type="checkbox" name="<?php echo $this->optionsKey; ?>[hide_size][<?php echo $post_type; ?>][<?php echo $thumb_name; ?>]" value="1" <?php echo checked($_checked); ?> />
								<?php echo $thumb_name;?> - <?php echo $data['width'];?>x<?php echo $data['height'];?> <?php /* echo ($data['crop'] == '1' ? '(cropped)' : '') */?>
							</label>
						</li>
					<?php endif; ?>
				<?php endforeach ?>
				
				</ul>
				<hr />
			</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	function callback_debug_js() {
		$options = get_option($this->optionsKey);
		$_id = 'debug_js';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$this->optionsKey.'['.$_id.']" id="'.$this->cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false) . ' />';
	}

	function callback_debug_data() {
		$options = get_option($this->optionsKey);
		$_id = 'debug_data';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$this->optionsKey.'['.$_id.']" id="'.$this->cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false ) . ' />';
	}

	function validateSettings($input) {
		$sizes = $this->getImageSizes();

		$post_types = $this->getPostTypes();

		$storeInDb = array();
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
	
	function sectionDescriptionTest() {?>
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

/* helper functions **********************************************************************************************/

	function ajax_callback_admin_quicktest() {
		//security
		if(!current_user_can('manage_options')) die('forbidden');
		check_ajax_referer('cpt_quicktest-ajax-nonce','security');//only for quicktest
		
		$report = array();
		$doDeleteAttachement = false;
		$doDeleteTempFile = false;
		$attachmentId = -1;
		
		$sourceFile = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'test_image.jpg';
		$tempFile = $this->getUploadDir().DIRECTORY_SEPARATOR.'testfile.jpg';
		try {
			//check if tmp-folder can be generated
			if(is_dir($this->getUploadDir())) {
				$report[] = '<strong class="success">success</strong> Temporary directory exists';
			} else {
				if (!mkdir($this->getUploadDir())) {
					throw new \Exception('<strong class="fails">fail</strong> Creating the temporary directory ('.esc_attr($this->getUploadDir()).') | is the upload-directory writable with PHP?');
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
			$_FILES['cpt_quicktest'] = array(
				'name' => 'test_image.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => $tempFile,
				'error' => 0,
				'size' => 102610
			);
			$attachmentId = media_handle_upload( 'cpt_quicktest', 0, array(), array( 'test_form' => false, 'action'=>'test' ) );
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
				false,						// * @param int $src_abs Optional. If the source crop points are absolute.
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
		
		echo join($report,"<br />");
		exit();
	}

	function getUploadDir() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'].DIRECTORY_SEPARATOR.'tmp';
	}


	/**
	 * get the post types and delete some prebuild post types that we dont need
	 */
	function getPostTypes() {
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
	 * </pre>
	 */
	function getImageSizes() {
		global $_wp_additional_image_sizes;//array with the available image sizes
		$image_size_names = array_flip(get_intermediate_image_sizes());
		foreach($image_size_names as $key=>$value) {
			$image_size_names[$key] = $key;
		}
		
		$tmp_sizes = apply_filters( 'image_size_names_choose', $image_size_names );
		$image_size_names = array_merge($image_size_names,$tmp_sizes);
		
		$sizes = array();
		foreach( $image_size_names as $_size=>$theName ) {

			if ( in_array( $_size, $this->defaultSizes ) ) {
				$sizes[ $_size ]['width']  = intval(get_option( $_size . '_size_w' ));
				$sizes[ $_size ]['height'] = intval(get_option( $_size . '_size_h' ));
				$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
			} else {
				$sizes[ $_size ] = array(
					'width'  => intval($_wp_additional_image_sizes[ $_size ]['width']),
					'height' => intval($_wp_additional_image_sizes[ $_size ]['height']),
					'crop'   => (bool) $_wp_additional_image_sizes[ $_size ]['crop']
				);
			}
			$sizes[ $_size ]['name'] = $theName;
		}
		$sizes = apply_filters('crop_thumbnails_image_sizes',$sizes);
		return $sizes;
	}

	function getOptions() {
		return get_option($this->optionsKey);
	}

	function getNonceBase() {
		return 'crop-post-thumbnails-nonce-base';
	}
}
$cptSettings = new CropThumbnailsSettings();
