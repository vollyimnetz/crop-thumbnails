<?php

/**
 * Contains all code inside the croping-window
 */

class CPT_ForbiddenException extends Exception {}

class CropPostThumbnailsEditor {

	private $debugOutput = '';

	private $allowedMime = array('image/jpeg','image/png');

	function __construct() {
		/* for the html inside the thickbox */
		add_action('wp_ajax_croppostthumb_ajax', array($this,'ajaxWindow'));
		
		add_action('wp_ajax_cpt_cropdata', array($this, 'provideCropData') );
	}
	
	public function provideCropData() {
		try {
			header('Content-Type: application/json; charset=UTF-8');
			$result = $this->getCropData();
			echo json_encode($result);
		} catch(InvalidArgumentException $e) {
			http_response_code(400);
			echo 'FAILURE while processing request: '.$e->getMessage();
		} catch(CPT_ForbiddenException $e) {
			http_response_code(403);
			echo 'ERROR not allowed.';
		} catch(Exception $e) {
			http_response_code(400);
			echo 'FAILURE while processing request.';
		}
		die();//to prevent to send back a "0"
	}
	
	public function getCropData() {
		if(!self::isUserPermitted()) {
			throw new CPT_ForbiddenException();
		}
		
		global $cptSettings;
		$options = $cptSettings->getOptions();
		$result = array(
			'options' => $options,
			'imageObj' => null,
			'postTypeFilter' => null,
			'imageSizes' => array_values($cptSettings->getImageSizes()),
			'fullSizeImage' => null, //???
			'lang' => array(
				'bug' => __('Bug--this should not have occurred.',CROP_THUMBS_LANG),
				'warningOriginalToSmall' => __('Warning: the original image is too small to be cropped in good quality with this thumbnail size.',CROP_THUMBS_LANG),
				'selectOne' => __('First, select an image. Then, click once again.',CROP_THUMBS_LANG),
				'cropDisabled' => __('Cropping is disabled for this post-type.',CROP_THUMBS_LANG),
				'waiting' => __('Please wait until the images are cropped.',CROP_THUMBS_LANG),
				'rawImage' => __('Raw',CROP_THUMBS_LANG),
				'pixel' => __('pixel',CROP_THUMBS_LANG),
				'instructions_header' => __('Quick Instructions',CROP_THUMBS_LANG),
				'instructions_step_1' => __('Step 1: Choose an image from the right.',CROP_THUMBS_LANG),
				'instructions_step_2' => __('Step 2: Use your mouse to change the size of the rectangle on the image above.',CROP_THUMBS_LANG),
				'instructions_step_3' => __('Step 3: Click on "Save Crop".',CROP_THUMBS_LANG),
				'label_crop' => __('Save Crop',CROP_THUMBS_LANG),
				'label_same_ratio' => __('Crop all images with same ratio at once',CROP_THUMBS_LANG),
				'label_deselect_all' => __('deselect all',CROP_THUMBS_LANG),
				'dimensions' => __('Dimensions:',CROP_THUMBS_LANG),
				'ratio' => __('Ratio:',CROP_THUMBS_LANG),
				'cropped' => __('cropped',CROP_THUMBS_LANG),
				'lowResWarning' => __('Original image size too small for good crop quality!',CROP_THUMBS_LANG),
			),
			'nonce' => wp_create_nonce($cptSettings->getNonceBase())
		);
		
		//simple validation
		if(empty($_REQUEST['imageId'])) {
			throw new InvalidArgumentException('Missing Parameter "imageId".');
		}
		
		$result['imageObj'] = get_post(intval($_REQUEST['imageId']));
		if(empty($result['imageObj']) || $result['imageObj']->post_type!=='attachment') {
			throw new InvalidArgumentException('Image with ID:'.intval($_REQUEST['imageId']).' could not be found');
		}

		if(!empty($_REQUEST['posttype']) && post_type_exists($_REQUEST['posttype'])) {
			$result['postTypeFilter'] = $_REQUEST['posttype'];
		}
		
		$result['fullSizeImage'] = $this->getUncroppedImageData($result['imageObj']->ID, 'full');
		$result['largeSizeImage'] = $this->getUncroppedImageData($result['imageObj']->ID, 'large');
		$result['hiddenOnPostType'] = self::shouldBeHiddenOnPostType($options,$current_parent_post_type);
		
		if(!$result['hiddenOnPostType']) {
			
			foreach($result['imageSizes'] as $key => $imageSize) {
				
				if(empty($imageSize['crop']) || $imageSize['width']<=0 || $imageSize['height']<=0) {
					//we do not need uncropped image sizes
					unset($result['imageSizes'][$key]);
					continue;//to the next entry
				}
				
				
				//DEFINE RATIO AND GCD
				//DEFAULT RATIO - defined by the defined image-size
				$ratioData = $this->calculateRatioData($imageSize['width'],$imageSize['height']);
				
				
				//DYNAMIC RATIO
				//the dynamic ratio is defined by the original image size and fix width OR height
				//@eee https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
				if($imageSize['width'] === 9999) {
					//if you define width with 9999 - it crops for the exect defined height but the full width
					$ratioData = $this->calculateRatioData($result['fullSizeImage']['width'], $imageSize['height']);
				} elseif($imageSize['height'] === 9999) {
					//if you define height with 9999 - it crops for the exect defined width but the full height
					$ratioData = $this->calculateRatioData($imageSize['width'], $result['fullSizeImage']['height']);
				}
				
				
				$img_data = wp_get_attachment_image_src($result['imageObj']->ID, $imageSize['name']);
				$jsonDataValues = array(
					'name' => $imageSize['name'],
					'nameLabel' => $imageSize['name'],//if you want to change the label of this image-size
					'url' => $img_data[0],
					'width' => $imageSize['width'],
					'height' => $imageSize['height'],
					'gcd' => $ratioData['gcd'],
					'ratio' => $ratioData['ratio'],
					'printRatio' => apply_filters('crop_thumbnails_editor_printratio', $ratioData['printRatio'], $imageSize['name']),
					'hideByPostType' => self::shouldSizeBeHidden($options,$imageSize,$result['postTypeFilter']),
					'crop' => true//legacy
				);
				
				$result['imageSizes'][$key] = apply_filters('crop_thumbnails_editor_jsonDataValues', $jsonDataValues);
				
			}//END froeach
		}
		
		if(is_array($result['imageSizes'])) $result['imageSizes'] = array_values($result['imageSizes']);
		return $result;
	}
	
	private function getUncroppedImageData($ID, $imageSize = 'full') {
		$orig_img = wp_get_attachment_image_src($ID, $imageSize);
		$orig_ima_gcd = $this->gcd($orig_img[1], $orig_img[2]);
		$result = array(
			'url' => $orig_img[0],
			'width' => $orig_img[1],
			'height' => $orig_img[2],
			'gcd' => $orig_ima_gcd,
			'ratio' => ($orig_img[1]/$orig_ima_gcd) / ($orig_img[2]/$orig_ima_gcd),
			'print_ratio' => ($orig_img[1]/$orig_ima_gcd).':'.($orig_img[2]/$orig_ima_gcd),
			'image_size' => $imageSize
		);
		return $result;
	}
	
	private function calculateRatioData($width,$height) {
		$gcd = $this->gcd($width,$height);
		$result = array(
			'gcd' => $gcd,
			'ratio' => ($width/$gcd) / ($height/$gcd),
			'printRatio' => $width/$gcd.':'.$height/$gcd
		);
		return $result;
	}


	/**
	 * this function is called for/from the thickbox - returns ordanary html
	 */
	function ajaxWindow() {
		$this->cleanWPHead();
		$failure_msg = '';
		if(!self::isUserPermitted()) {
			$failure_msg = __('You are not allowed to do this.',CROP_THUMBS_LANG);
		} else {
			switch(true) {
				case isset($_REQUEST['image_id'])://only one image
					$this->byImageId();
					break;
				default:
					$failure_msg = __('An error occurred!',CROP_THUMBS_LANG);
					break;
			}
		}

		if(!empty($failure_msg)) {
			$windowCssPath = apply_filters('crop_post_thumbnail_window_css', plugins_url('css/cpt-window.css',dirname(__FILE__)));
			wp_enqueue_style( 'cpt-window',$windowCssPath,array('wp-admin'),CROP_THUMBS_VERSION);
			$cptContent = $failure_msg;
			include_once( dirname(__FILE__).'/../html/template.php' );
		}
		die();//to prevent to send back a "0"
	}


	/**
	 * Display the crop editor.
	 * @param $_REQUEST['image_id'] - ID of the image to show
	 * @param $_REQUEST['viewmode']=='single' - without the back-link
	 * @param $_REQUEST['posttype']=='page' - (optional) will be used to hide certain image sizes (default: '')
	 */
	function byImageId() {
		global $cptSettings,$content_width;
		
		//make sure $content_width is out of the way
		$_remember_content_width = $content_width;
		$content_width = null;


		$options = $cptSettings->getOptions();
		$this->addDebug('options', print_r($options,true));
		$image_obj = get_post(intval($_REQUEST['image_id']));

		//$post_id_attached holds the id of the post the image is attached to - can be null/empty
		$post_id_attached = -1;
		if(!empty($image_obj->post_parent)) {
			$post_id_attached=$image_obj->post_parent;
		}

		//$current_parent_post_type
		$current_parent_post_type = '';
		$current_parent_post_id = -1;

		if(!empty($_REQUEST['parent_post_id'])) {
			$_tmp = get_post(intval($_REQUEST['parent_post_id']));
			if(!empty($_tmp)) {
				$current_parent_post_type = $_tmp->post_type;
				$current_parent_post_id = $_tmp->ID;
			}
		}
		
		if(!empty($_REQUEST['posttype']) && post_type_exists($_REQUEST['posttype'])) {
			$current_parent_post_type = $_REQUEST['posttype'];
		}

		$all_image_sizes = $cptSettings->getImageSizes();
		$this->addDebug('all_image_sizes', print_r($all_image_sizes,true));

		$orig_img = wp_get_attachment_image_src($image_obj->ID, 'full');
		$orig_img['gcd'] = $this->gcd($orig_img[1],$orig_img[2]);
		$orig_img['ratio'] = ($orig_img[1]/$orig_img['gcd']) / ($orig_img[2]/$orig_img['gcd']);
		$orig_img['print_ratio'] = ($orig_img[1]/$orig_img['gcd']).':'.($orig_img[2]/$orig_img['gcd']);

		$cache_breaker = time();//a additional parameter that will be added to the image-urls to prevent the browser to show a cached image

		$this->addDebug('img-postmeta',print_r(wp_get_attachment_metadata($image_obj->ID, true),true));

		//the javascript
		ob_start(); ?>
<script>
jQuery(document).ready(function($) {
	cpt_lang = new Object();
	cpt_lang['bug'] = "<?php _e('Bug--this should not have occurred.',CROP_THUMBS_LANG);?>";
	cpt_lang['warningOriginalToSmall'] = "<?php _e('Warning: the original image is too small to be cropped in good quality with this thumbnail size.',CROP_THUMBS_LANG);?>";
	cpt_lang['selectOne'] = "<?php _e('First, select an image. Then, click once again.',CROP_THUMBS_LANG);?>";
	cpt_ajax_nonce = "<?php echo wp_create_nonce($cptSettings->getNonceBase()); ?>";
	cpt_debug_js = <?php echo (!empty($options['debug_js'])) ? 'true;' : 'false;'; ?>
});
</script>
		<?php
		$cptScript = ob_get_clean();
		//END the javascript

		/**
		 * wether or not to show the "back button"
		 */
		$headline = true;
		if(!empty($_REQUEST['viewmode']) && $_REQUEST['viewmode']=='single') {
			$headline = false;
		}

		//the content
		ob_start();

		if(self::shouldBeHiddenOnPostType($options,$current_parent_post_type)) : ?>
			<div class="cpt-crop-view">
				<div class="postTypeDisabledMsg"><?php _e('Cropping is disabled for this post-type.',CROP_THUMBS_LANG); ?></div>
			</div>
		<?php else : ?>

		<div class="cpt-crop-view">
			<?php if($headline) :?><div class="header"><a class="back" href="<?php echo admin_url( 'admin-ajax.php'); ?>?action=croppostthumb_ajax&post_id=<?php echo $current_parent_post_id; ?>"><?php _e('back to image list',CROP_THUMBS_LANG); ?></a></div><?php endif; ?>
			<div class="waitingWindow hidden"><?php _e('Please wait until the images are cropped.',CROP_THUMBS_LANG); ?></div>
			<div class="mainWindow">
				<div class="selectionArea cptLeftPane">
					<h3><?php _e('Raw',CROP_THUMBS_LANG); ?>: <?php echo $orig_img[1].' '.__('pixel',CROP_THUMBS_LANG)?>  x <?php echo $orig_img[2].' '.__('pixel',CROP_THUMBS_LANG) ?> (<?php echo $orig_img['print_ratio']; ?>)</h3>
					<div class="cropContainer">
						<img id="cpt-croppingImage" src="<?php echo $orig_img[0]?>" data-values='{"id":<?php echo $image_obj->ID; ?>,"parentId":<?php echo $post_id_attached ?>,"width":<?php echo $orig_img[1]?>,"height":<?php echo $orig_img[2] ?>}' />
					</div>
					<button id="cpt-generate" class="button"><?php _e('Save Crop',CROP_THUMBS_LANG);?></button>
					<h4><?php _e('Quick Instructions',CROP_THUMBS_LANG);?></h4>
					<ul class="step-info">
						<li><?php _e('Step 1: Choose an image from the right.',CROP_THUMBS_LANG); ?></li>
						<li><?php _e('Step 2: Use your mouse to change the size of the rectangle on the image above.',CROP_THUMBS_LANG); ?></li>
						<li><?php _e('Step 3: Click on "Save Crop".',CROP_THUMBS_LANG); ?></li>
					</ul>
				</div>
				<div class="cptRightPane">
					<input type="checkbox" name="cpt-same-ratio" value="1" id="cpt-same-ratio" checked="checked" />
					<label for="cpt-same-ratio" class="lbl-cpt-same-ratio"><?php _e('Crop all images with same ratio at once',CROP_THUMBS_LANG); ?></label>
					<button id="cpt-deselect" class="button"><?php _e('deselect all',CROP_THUMBS_LANG); ?></button>
					<ul class="thumbnail-list">
						<?php
						foreach($all_image_sizes as $img_size_name=>$value) :

							if ($value['height'] == 9999) {
								$value['height'] = 0;
							}
							if ($value['width'] == 9999) {
								$value['width'] = 0;
							}

							if(!self::shouldSizeBeHidden($options,$value,$current_parent_post_type)) :
								$ratio = null;			//reset
								$gcd = null;			//reset
								$print_ratio = null;	//reset
								$print_cropped = '';	//reset
								$crop = 0;				//reset
								$special_warning = '';  //reset


								/** define ratio **/
								if($value['width'] != 0 && $value['height']!=0) {
									$gcd = $this->gcd($value['width'],$value['height']);//get greatest common divisor
									$ratio = ($value['width']/$gcd) / ($value['height']/$gcd);//get ratio
									$print_ratio = $value['width']/$gcd.':'.$value['height']/$gcd;
								} else {
									//keep ratio same as original image
									$gcd = $orig_img['gcd'];
									$ratio = $orig_img['ratio'];
									$print_ratio = $orig_img['print_ratio'];
								}


								if(!empty($value['crop'])) {
									//cropped
									$print_cropped = ' ('.__('cropped',CROP_THUMBS_LANG).')';
									$crop = 1;
								} else {
									//not cropped
									/* -- maybe use this behaviour in a later version --
									$print_cropped = ' ('.__('maximum',CROP_THUMBS_LANG).')';
									$print_ratio = __('free choice',CROP_THUMBS_LANG);
									 */

									$print_cropped = ' ('.__('maximum',CROP_THUMBS_LANG).')';
									$crop = 1;
									//keep ratio same as original image
									$gcd = $orig_img['gcd'];
									$ratio = $orig_img['ratio'];
									$print_ratio = $orig_img['print_ratio'];
								}

								$print_dimensions = $value['width'].' '.__('pixel',CROP_THUMBS_LANG).' x '.$value['height'].' '.__('pixel',CROP_THUMBS_LANG).$print_cropped;

								$img_data = wp_get_attachment_image_src($image_obj->ID, $img_size_name);


								$_lowResWarning = '';
								if(self::isLowRes($value,$orig_img)) {
									$_lowResWarning = ' <span class="lowResWarning">'.__('Original image size too small for good crop quality!',CROP_THUMBS_LANG).'</span>';
								}
								
								$jsonDataValues = array(
									'name' => $img_size_name,
									'width' => $value['width'],
									'height' => $value['height'],
									'ratio' => $ratio,
									'crop' => $crop
								);
								$jsonDataValues = apply_filters('crop_thumbnails_editor_jsonDataValues', $jsonDataValues);
								$print_ratio = apply_filters('crop_thumbnails_editor_printratio', $print_ratio, $img_size_name);

								?>
								<li rel="<?php echo $print_ratio; ?>">
									<strong title="<?php esc_attr_e($img_size_name) ?>"><?php echo $value['name'] ?><?php echo $_lowResWarning; ?></strong><?php echo $special_warning; ?>
									<span class="dimensions"><?php _e('Dimensions:',CROP_THUMBS_LANG) ?> <?php echo $print_dimensions; ?></span>
									<span class="ratio"><?php _e('Ratio:',CROP_THUMBS_LANG) ?> <?php echo $print_ratio; ?></span>
									<img src="<?php echo $img_data[0]?>?<?php echo $cache_breaker ?>" data-values="<?php esc_attr_e(json_encode($jsonDataValues)); ?>" />
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
		endif;
		$cptContent = ob_get_clean();
		$cptContent.= $this->getDebugOutput($options);
		//END the content


		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'ctp_cropperjs', plugins_url('js/app/vendor/cropper.min.js',dirname(__FILE__)), array(), CROP_THUMBS_VERSION);
		wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'cpt_crop',  plugins_url('js/cpt-crop.js',dirname(__FILE__)), array('jquery','imagesloaded','json2','ctp_cropperjs'), CROP_THUMBS_VERSION);

		$windowCssPath = apply_filters('crop_post_thumbnail_window_css', plugins_url('css/cpt-window.css',dirname(__FILE__)));
		wp_enqueue_style( 'cpt_window',$windowCssPath,array('wp-admin'),CROP_THUMBS_VERSION);
		wp_enqueue_style( 'ctp_cropperjs', plugins_url('js/app/vendor/cropper.min.css',dirname(__FILE__)), array(), CROP_THUMBS_VERSION);

		include_once( dirname(__FILE__).'/../html/template.php' );

		$content_width = $_remember_content_width;//reset the content-width
		return true;
	}

	private static function shouldBeHiddenOnPostType($options,$post_type) {
		if(empty($post_type)) {
			return false;
		}
		if(empty($options['hide_post_type'][$post_type])) {
			return false;
		}
		return true;
	}

	/**
	 * Check wether or not the image_size should be hidden for this post_type
	 * @param array options array
	 * @param array the image-size (i.e. post-thumbnail, ...)
	 * @param string name post-type (i.e. post, page, ...)
	 * @return boolean true if Image-size should be hidden
	 */
	private static function shouldSizeBeHidden($options, $img_size, $post_type='') {
		$_return = false;
		if(!empty($post_type)) {
			//we are NOT in the mediathek

			//-if hide_size
			if(!empty($options['hide_size'][$post_type][ $img_size['name'] ])) {
				$_return = true;
			}

			//if not a crop-size and allow_non_cropped
			if(empty($img_size['crop']) && empty($options['allow_non_cropped'])) {
				$_return = true;
			}
		} else {
			//we are in the mediathek

			//-if not a crop-size and allow_non_cropped
			if(empty($img_size['crop']) && empty($options['allow_non_cropped'])) {
				$_return = true;
			}
		}
		return $_return;
	}


	/**
	 * Checks if the thumb-image-dimensions are bigger than the actuall image.
	 * @param array thumbnail-data from the add_image_size-funtion (width, height)
	 * @param array original image-data-array (url, width, height)
	 * @return true if the original is smaller than the thumbnail-size
	 */
	private static function isLowRes($thumb,$orig) {
		if($thumb['width']>$orig[1] || $thumb['height']>$orig[2]) {
			return true;
		}
		return false;
	}

	private static function isUserPermitted() {
		$return = false;
		if(current_user_can('upload_files')) {
			$return = true;
		}
		//TODO maybe add noence (is it needed? there are no file- or db-operations)
		return $return;
	}


	/**
	 * This is for use inside the plugin only.
	 * Removes all other styles and scripts, to make sure the crop-thumbnail is not compromited by other plugins
	 */
	function cleanWPHead() {
		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles = new WP_Styles();
		remove_all_actions('wp_print_styles');
		remove_all_actions('wp_print_scripts');
	}

	/**
	 * Greatest cummon divisor
	 */
	private function gcd($a, $b) {
		if(function_exists('gmp_gcd')) {
			$gcd = gmp_strval(gmp_gcd($a,$b));
			$this->addDebug("gcd-version", "gmp_gcd:".$gcd);
			return ($gcd);
		} else {
			$gcd = self::my_gcd($a,$b);
			$this->addDebug("gcd-version", "my_gcd:".$gcd);
			return $gcd;
		}
	}

	private static function my_gcd($a, $b) {
		$b = ( $a == 0 )? 0 : $b;
		return ( $a % $b )? self::my_gcd($b, abs($a - $b)) : $b;
	}


	function addDebug($title, $output) {
		$this->debugOutput.= '---'.$title.'---<br />'.$output.'<br />';
	}


	function getDebugOutput($options) {
		if(!empty($options['debug_data'])) {
			return '<div class="cpt-debug closed"><a class="cpt-debug-handle" href="#">show debug</a><div class="content">'.nl2br(str_replace("    ","&nbsp;&nbsp;",$this->debugOutput)).'</div></div>';
		}
		return '';
	}
}



$cpte = new CropPostThumbnailsEditor();
