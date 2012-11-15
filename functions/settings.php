<?php
class CropThumbnailsSettings {
	private $uniqeSettingsId = 'cpt-settings';
	private $optionsKey = 'crop-post-thumbs';
	private $standardSizes = array('thumbnail','medium','large');
	
	function __construct() {
		add_action('admin_menu', array($this,'addOptionsPage'));
	}
	
	function addOptionsPage() {
		add_options_page(__('Crop Post Thumbnail Page',CPT_LANG), __('Crop Thumbnails',CPT_LANG), 'manage_options', 'page-cpt', array($this,'optionsPage'));
		add_action('admin_init', array($this,'settingsInitialisation'));
	}
	
	function optionsPage() { ?>
		<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2><?php esc_attr_e('Crop Thumbnails - Settings',CPT_LANG); ?></h2>
			<form action="options.php" method="post">
			<?php settings_fields($this->uniqeSettingsId); ?>
			<?php do_settings_sections('page1'); ?>
			 
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes',CPT_LANG); ?>" class="button-primary" />
			</form>
		
		</div>
		<?php
	}
	
	function settingsInitialisation(){
		register_setting( $this->uniqeSettingsId, $this->optionsKey, array($this,'validateSettings') );
		add_settings_section('choose_sizes_section', __('Sizes and Posttypes',CPT_LANG), array($this,'showSectionDescriptionChooseSizes'), 'page1');
		add_settings_field('sizes', __('Choose the image-sizes you want to hide. Choose a post-type to prevent any use of the plugin for these entries.',CPT_LANG), array($this,'chooseSizeInputFields'), 'page1', 'choose_sizes_section');
	}
	
	function showSectionDescriptionChooseSizes() {?>
		<p>
			<?php _e('Crop-Thumbnails is created to make cropping easy for the user. Often times the user only need to crop one, in dependence of the post-type. But the system will create also all other sizes. So, here you can select for what post-type what sizes should be visible in the plugin interface.',CPT_LANG) ?>
			<br /><strong><?php _e('Crop-Thumbnails will only show croped images - sizes with no crop will always be hidden.',CPT_LANG); ?></strong>
		</p>
		<?php
	}

	function chooseSizeInputFields() {
		//get all the data
		$options = get_option($this->optionsKey);
		#echo '<pre>'.print_r($options,true).'</pre>';
		$post_types = $this->getPostTypes();
		$image_sizes = $this->getImageSizes();
		
		//output
		echo '<ul>';
		foreach($post_types as $post_type=>$value) { ?>
			<li>
				<label for="cpt-<?php echo $post_type; ?>">
					<input id="cpt-<?php echo $post_type;?>" type="checkbox" name="<?php echo $this->optionsKey; ?>[hide_post_type][<?php echo $post_type;?>]" value="1" <?php checked(isset($options['hide_post_type'][$post_type]),true); ?> />
					<strong><?php echo $value->labels->name; ?></strong>
				</label>
				<ul style="margin:1em;">
				<?php 
				
				foreach($image_sizes as $thumb_name => $data) :
					$_checked = false;
					if(is_array($options['hide_size']) && !empty($options['hide_size'][$post_type][$thumb_name])) {
						$_checked = true;
					}
					if($data['crop']=='1') : 
						 ?>
						<li>
							<label for="cpt-<?php echo $post_type;?>-<?php echo $thumb_name;?>">
								<input id="cpt-<?php echo $post_type;?>-<?php echo $thumb_name;?>" type="checkbox" name="<?php echo $this->optionsKey; ?>[hide_size][<?php echo $post_type; ?>][<?php echo $thumb_name; ?>]" value="1" <?php echo checked($_checked); ?> />
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
	 * Creates an array of all image sizes:
	 * array[sizename][height]
	 * array[sizename][width]
	 * array[sizename][crop] = boolean
	 *
	 * Thanks to the ajax_thumbnail_rebuild plugin and post-thumbnail-editor
	 */
	function getImageSizes() {
		global $_wp_additional_image_sizes;//array with the available image sizes
		$tmp_sizes = get_intermediate_image_sizes();
		$sizes = array();
		foreach ($tmp_sizes as $s){
			//width
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) {// theme-added size
				$width = intval( $_wp_additional_image_sizes[$s]['width'] );
			} else { // default sizes set in options
				$width = get_option( "{$s}_size_w" );
			}
			//height
			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) {// theme-added size
				$height = intval( $_wp_additional_image_sizes[$s]['height'] );
			} else { // default sizes set in options
				$height = get_option( "{$s}_size_h" );
			}
			//crop
			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) {// theme-added size
				$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
			} else { // default sizes set in options
				$crop = get_option( "{$s}_crop" );
			}
			//join
			$sizes[$s] = array(
				'width'  => $width,
				'height' => $height,
				'crop'   => $crop
			);
		}
		#print_r($sizes);
		return $sizes;
	}
	
	function validateSettings($input) {
		$sizes = $this->getImageSizes();
		
		$post_types = $this->getPostTypes();
		
		$storeInDb = array();
		//check input[hide_post_type] --> are the post_types real there
		foreach($input['hide_post_type'] as $_post_type_name=>$value) {
			if(isset($post_types[$_post_type_name])) {
				$storeInDb['hide_post_type'][$_post_type_name] = '1';
			}
		}
		
		//check $input[sizes] --> are post_types correct, are sizes real there
		foreach($input['hide_size'] as $_post_type_name=>$size_type) {
			if(isset($post_types[$_post_type_name])) {
				foreach($size_type as $_size_name=>$value) {
					if(isset($sizes[$_size_name])) {
						$storeInDb['hide_size'][$_post_type_name][$_size_name] = '1';
					}
				}
			}
		}
		
		return $storeInDb;
	}
	
	function getOptions() {
		return get_option($this->optionsKey);
	}
	
	function getNonceBase() {
		return 'crop-post-thumbnails-nonce-base';
	}
}
$cptSettings = new CropThumbnailsSettings();
?>