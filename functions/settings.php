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
		/*
		$_sectionID = 'experimental';
		add_settings_section($_sectionID, __('Experimental Settings',CROP_THUMBS_LANG), array($this,'emptySectionDescription'), 'page1');
		$_tmpID = 'allow_non_cropped';
		add_settings_field($_tmpID, __('Allow non cropped image-sizes.',CROP_THUMBS_LANG), 	array($this,'callback_'.$_tmpID), 'page1', $_sectionID, array( 'label_for' => $this->cssPrefix.$_tmpID ));
		*/
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
		echo '<ul>';
		foreach($post_types as $post_type=>$value) { ?>
			<li>
				<label for="<?php echo $this->cssPrefix.$post_type; ?>">
					<input id="<?php echo $this->cssPrefix.$post_type;?>" type="checkbox" name="<?php echo $this->optionsKey; ?>[hide_post_type][<?php echo $post_type;?>]" value="1" <?php checked(isset($options['hide_post_type'][$post_type]),true); ?> />
					<strong><?php echo $value->labels->name; ?></strong>
				</label>
				<ul style="margin:1em;">
				<?php

				foreach($image_sizes as $thumb_name => $data) :
					$_checked = false;
					if(!empty($options['hide_size']) && is_array($options['hide_size']) && !empty($options['hide_size'][$post_type][$thumb_name])) {
						$_checked = true;
					}
					if($data['crop']=='1') :
						 ?>
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
			<?php
		}
		echo '</ul>';
	}

	/**
	 * currently not used
	 */
	function callback_allow_non_cropped() {
		$options = get_option($this->optionsKey);
		$_id = 'allow_non_cropped';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$this->optionsKey.'['.$_id.']" id="'.$this->cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false) . ' />';
		?>
		<div class="info">
			<?php _e('ATTENTION: be aware that you can break things, when you activate this. When activated your are able to cut those images to a spezific dimension that are not cropped. The name of the image will not change. You should be extra carefull when:',CROP_THUMBS_LANG) ?>
			<ul>
				<li><?php _e('you had inserted the image before on any page or post. (There may be height and width stored directly in the page-content.)',CROP_THUMBS_LANG); ?></li>
				<li><?php _e('you use a plugin that expect the original image size. (The original image-size is also "stored" in the filename.)',CROP_THUMBS_LANG); ?></li>
			</ul>
			<p><?php _e('The "full" image-size will never be cropped, otherwise you are not able to restore any image-size.',CROP_THUMBS_LANG); ?></p>
		</div>
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

		/* Experimental Section */
		$_tmpID = 'allow_non_cropped';
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

/* helper functions **********************************************************************************************/


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
		$tmp_sizes = array_flip(get_intermediate_image_sizes());
		foreach($tmp_sizes as $key=>$value) {
			$tmp_sizes[$key] = $key;
		}
		$tmp_sizes = apply_filters( 'image_size_names_choose', $tmp_sizes );
		
		$sizes = array();
		foreach( $tmp_sizes as $_size=>$theName ) {

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
