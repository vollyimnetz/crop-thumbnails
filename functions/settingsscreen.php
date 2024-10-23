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
		}
	}

	public function optionsPageStyle() {
		if(!empty($_REQUEST['page']) && $_REQUEST['page']==='page-cpt') {
			wp_enqueue_style('crop-thumbnails-options-style', plugins_url('app/main.css', __DIR__), [], CROP_THUMBNAILS_VERSION);
			if(function_exists('wp_enqueue_script_module')) {
				wp_enqueue_script_module('crop-thumbnails-options-js', plugins_url('app/main.js', __DIR__ ), ['wp-api'], CROP_THUMBNAILS_VERSION);
			} else {
				wp_enqueue_script('crop-thumbnails-options-js', plugins_url('app/main.js', __DIR__ ), ['wp-api'], CROP_THUMBNAILS_VERSION);
			}
			wp_enqueue_script('wp-api');//wp_enqueue_script_module seem not to load wp-api correctly
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
	}

	public function optionsPage() { ?>
		<div class="wrap cropThumbnailSettingsPage">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2>Crop-Thumbnails <?php esc_attr_e('Settings','crop-thumbnails'); ?></h2>

			<div id="<?php echo self::$cssPrefix ?>settingsscreen">
				<cpt-settingsscreen></cpt-settingsscreen>
			</div>
		</div>
		<?php
	}
}
$cptSettingsScreen = new SettingsScreen();
