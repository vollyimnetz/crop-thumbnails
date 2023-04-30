<?php
namespace crop_thumbnails;

class SettingsScreen {
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
			wp_enqueue_style('crop-thumbnails-options-style', plugins_url('app/main.css', __DIR__), [], CROP_THUMBNAILS_VERSION);
			wp_enqueue_script('crop-thumbnails-options-js', plugins_url('app/main.js', __DIR__ ), [], CROP_THUMBNAILS_VERSION);
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

			<div id="<?php echo self::$cssPrefix ?>settingsscreen">
				<cpt-settingsscreen settings="<?php echo esc_attr(json_encode($settings)) ?>"></cpt-settingsscreen>
			</div>
			
			<?php /*
			do_settings_sections('page1'); ?>
			
			<div class="<?php echo self::$cssPrefix ?>submit">
				<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes','crop-thumbnails'); ?>" class="button-primary" />
			</div>
			*/
			?>

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
	}

	public function sectionDescriptionChooseSizes() {
		$this->vueSettingsScreen();
	}

	public function emptySectionDescription() {/*empty*/ }


	
	public function callback_user_permission_only_on_edit_files() {
		/*
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$_id = 'user_permission_only_on_edit_files';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey().'['.$_id.']" id="'.self::$cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false) . ' />';
		?>
		<div class="<?php echo self::$cssPrefix ?>submit">
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes','crop-thumbnails'); ?>" class="button-primary" />
		</div>
		<?php
		*/
	}

	public function callback_debug_js() {
		/*
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$_id = 'debug_js';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey().'['.$_id.']" id="'.self::$cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false) . ' />';
		*/
	}

	public function callback_debug_data() {
		/*
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$_id = 'debug_data';
		if(empty($options[$_id])) { $options[$_id] = ''; }
		echo '<input name="'.$GLOBALS['CROP_THUMBNAILS_HELPER']->getOptionsKey().'['.$_id.']" id="'.self::$cssPrefix.$_id.'" type="checkbox" value="1" ' . checked( 1, $options[$_id], false ) . ' />';
		*/
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
	
	public function sectionDescriptionTest() {
	}


	public function ajax_callback_admin_quicktest() {
		exit();
	}
}
$cptSettingsScreen = new SettingsScreen();
