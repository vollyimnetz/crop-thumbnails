<?php
class CropPostThumbnailsEditor {
	
	private $debugOutput = '';
	
	function __construct() {
		if ( is_admin() ) {
			//add style and javascript
			add_action( 'admin_print_styles', array(&$this, 'adminHeaderCSS') );
			add_action( 'admin_print_scripts', array(&$this, 'adminHeaderJS') );
		}
		
		/* for the html inside the thickbox */
		add_action('wp_ajax_croppostthumb_ajax', array($this,'ajaxWindow'));
	}
	
	
	/**
	 * this function is called for/from the thickbox - returns ordanary html
	 */
	function ajaxWindow() {
		$this->cleanWPHead();
		$failure_msg = '';
		if(!$this->isUserPermitted()) {
			$failure_msg = __('You are not allowed to do this.',CROP_THUMBS_LANG);
		} else {
			switch(true) {
				case isset($_REQUEST['post_id'])://full programm
					$this->listImages();
					break;
				case isset($_REQUEST['image_id'])://only one image
					$this->byImageId();
					break;
				case isset($_REQUEST['image_by_post_id'])://only one image
					$id = get_post_thumbnail_id(intval($_REQUEST['image_by_post_id']));
					if(!empty($id)) {
						$_REQUEST['image_id'] = $id;
						$_REQUEST['parent_post_id'] = intval($_REQUEST['image_by_post_id']); 
						$this->byImageId();
					} else {
						$failure_msg = '<div class="listEmptyMsg">'.__('No featured Image set for this post until now.',CROP_THUMBS_LANG).'</div>';
					}
					break;
				default:
					$failure_msg = __('An error happend!',CROP_THUMBS_LANG);
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
	 * Display a list of images that are attached to this post_id.
	 * Hightlight the post-thumbnail (if it is attached to this post_id)
	 */
	function listImages() {
		global $cptSettings;
		$options = $cptSettings->getOptions();
		
		$data = $this->loadPostIdData(intval($_REQUEST['post_id']));
		
		$parent_post_type = '';
		$_tmp_post = get_post(intval($_REQUEST['post_id']));
		if(!empty($_tmp_post)) {
			$parent_post_type = $_tmp_post->post_type;
		}
		
		$cptContent = '';
		
		if($this->shouldBeHiddenOnPostType($options,$parent_post_type)) {
			 $cptContent = '<div class="postTypeDisabledMsg">'.__('Cropping is disabled for this post-type.',CROP_THUMBS_LANG).'</div>';
		} elseif($data==false) {
			$cptContent = '<div class="listEmptyMsg">'.__('No images in this post yet. You have to upload some via upload dialog.',CROP_THUMBS_LANG).'</div>';
		} else {
			//the dynamic javascript
			ob_start(); ?>
	<script>
	jQuery(document).ready(function($) {
		$('.image-list .entry').click(function() {
			var image_id = $(this).attr('rel');
			var parent_post_id = <?php echo intval($_REQUEST['post_id']); ?>;
			document.location.href = ajaxurl+"?action=croppostthumb_ajax&image_id="+image_id+"&parent_post_id="+parent_post_id;
			return;
		});
	});
	</script>
			<?php
			$cptScript = ob_get_clean();
			//END the javascript
			
			
			//the content
			ob_start();?>
			<div class="header"><strong><?php _e('Choose the image you want to crop.',CROP_THUMBS_LANG); ?></strong></div>
			<ul class="image-list">
			<?php
			$counter = 1;
			foreach($data as $key=>$image) : ?>
				<li class="entry cursor<?php echo (isset($image->is_post_thumbnail) ? ' post-thumbnail' : ''); ?>" rel="<?php echo $image->ID;?>">
					<h3><?php echo (isset($image->is_post_thumbnail) ? __('Post Thumbnail',CROP_THUMBS_LANG) : sprintf(__('Image %d',CROP_THUMBS_LANG),$counter));?></h3>
					<?php $img_data = wp_get_attachment_image_src($image->ID, 'thumbnail');?>
					<img src="<?php echo $img_data[0].'?'.time(); ?>" />
				</li>
			<?php
				$counter++; 
			endforeach; ?>
			</ul>
			<?php
			$cptContent = ob_get_clean();
			//END the content
		}
		wp_enqueue_script( 'jquery' );
		
		$windowCssPath = apply_filters('crop_post_thumbnail_window_css', plugins_url('css/cpt-window.css',dirname(__FILE__)));
		wp_enqueue_style( 'cpt-window',$windowCssPath,array('wp-admin'),CROP_THUMBS_VERSION);
		include_once( dirname(__FILE__).'/../html/template.php' );
		return true;
	}


	/**
	 * Display the crop editor.
	 * @param $_REQUEST['image_id'] - ID of the image to show
	 * @param $_REQUEST['viewmode']=='single' - without the back-link
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
	cpt_lang['bug'] = "<?php _e('bug - this case shouldnt be happend',CROP_THUMBS_LANG);?>";
	cpt_lang['warningOriginalToSmall'] = "<?php _e('Warning: the original image is to small to be cropped in good quality with this thumbnail-size.',CROP_THUMBS_LANG);?>";
	cpt_lang['selectOne'] = "<?php _e('First, select one image. Then, click once again.',CROP_THUMBS_LANG);?>";
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
		
		if($this->shouldBeHiddenOnPostType($options,$current_parent_post_type)) : ?>
			<div class="cpt-crop-view">
				<div class="postTypeDisabledMsg"><?php _e('Cropping is disabled for this post-type.',CROP_THUMBS_LANG); ?></div>
			</div>
		<?php else : ?>
		
		<div class="cpt-crop-view">
			<?php if($headline) :?><div class="header"><a class="back" href="<?php echo admin_url( 'admin-ajax.php'); ?>?action=croppostthumb_ajax&post_id=<?php echo $current_parent_post_id; ?>"><?php _e('back to image-list',CROP_THUMBS_LANG); ?></a></div><?php endif; ?>
			<div class="waitingWindow hidden"><?php _e('Please wait until the Images are cropped.',CROP_THUMBS_LANG); ?></div>
			<div class="mainWindow">
				<div class="selectionArea cptLeftPane">
					<h3><?php _e('Raw',CROP_THUMBS_LANG); ?>: <?php echo $orig_img[1].' '.__('pixel',CROP_THUMBS_LANG)?>  x <?php echo $orig_img[2].' '.__('pixel',CROP_THUMBS_LANG) ?> (<?php echo $orig_img['print_ratio']; ?>)</h3>
					<img src="<?php echo $orig_img[0]?>" data-values='{"id":<?php echo $image_obj->ID; ?>,"parentId":<?php echo $post_id_attached ?>,"width":<?php echo $orig_img[1]?>,"height":<?php echo $orig_img[2] ?>}' />
					<button id="cpt-generate" class="button"><?php _e('save crop',CROP_THUMBS_LANG);?></button>
					<h4><?php _e('Quick-Instructions',CROP_THUMBS_LANG);?></h4>
					<ul class="step-info">
						<li><?php _e('Step 1: Choose an image from the right.',CROP_THUMBS_LANG); ?></li>
						<li><?php _e('Step 2: Use the mouse change the size of the rectangle on the image above.',CROP_THUMBS_LANG); ?></li>
						<li><?php _e('Step 3: Click on "save crop".',CROP_THUMBS_LANG); ?></li>
					</ul>
				</div>
				<div class="cptRightPane">
					<input type="checkbox" name="cpt-same-ratio" value="1" id="cpt-same-ratio" checked="checked" />
					<label for="cpt-same-ratio" class="lbl-cpt-same-ratio"><?php _e('select images with same ratio at once',CROP_THUMBS_LANG); ?></label>
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
							
							if(!$this->shouldSizeBeHidden($options,$img_size_name,$value,$current_parent_post_type)) :
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
								if($this->isLowRes($value,$orig_img)) {
									$_lowResWarning = ' <span class="lowResWarning">'.__('Original image to small for good crop-quality!',CROP_THUMBS_LANG).'</span>';
								}
								
							?>
							<li rel="<?php echo $print_ratio; ?>">
								<strong><?php echo $img_size_name.$_lowResWarning; ?></strong><?php echo $special_warning; ?>
								<span class="dimensions"><?php _e('Dimensions:',CROP_THUMBS_LANG) ?> <?php echo $print_dimensions; ?></span>
								<span class="ratio"><?php _e('Ratio:',CROP_THUMBS_LANG) ?> <?php echo $print_ratio; ?></span>
								<img src="<?php echo $img_data[0]?>?<?php echo $cache_breaker ?>" data-values='{"name":"<?php echo $img_size_name; ?>","width":<?php echo $value['width']; ?>,"height":<?php echo $value['height']; ?>,"ratio":<?php echo number_format($ratio, 13, '.', ''); ?>,"crop":<?php echo $crop ?>}' />
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
		wp_enqueue_script( 'my_jcrop', plugins_url('js/jcrop/js/jquery.Jcrop.min.js',dirname(__FILE__)), array(), CROP_THUMBS_VERSION);
		wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'cpt-crop',  plugins_url('js/cpt-crop.js',dirname(__FILE__)), array('jquery','my_jcrop','json2'), CROP_THUMBS_VERSION);
		
		$windowCssPath = apply_filters('crop_post_thumbnail_window_css', plugins_url('css/cpt-window.css',dirname(__FILE__)));
		wp_enqueue_style( 'cpt-window',$windowCssPath,array('wp-admin'),CROP_THUMBS_VERSION);
		wp_enqueue_style( 'my_jcrop', plugins_url('js/jcrop/css/jquery.Jcrop.min.css',dirname(__FILE__)), array(), CROP_THUMBS_VERSION);
		
		include_once( dirname(__FILE__).'/../html/template.php' );
		
		$content_width = $_remember_content_width;//reset the content-width
		return true;
	}

	function shouldBeHiddenOnPostType($options,$post_type) {
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
	 * @param string name of the image-size (i.e. post-thumbnail, ...)
	 * @param string name post-type (i.e. post, page, ...)
	 * @return boolean true if Image-size should be hidden
	 */
	function shouldSizeBeHidden($options, $img_size_name, $img_size, $post_type='') {
		$_return = false;
		if(!empty($post_type)) {
			//we are NOT in the mediathek
			
			//-if hide_size
			if(!empty($options['hide_size'][$post_type][$img_size_name])) {
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
	 * load all image data of that $post_id
	 * - adds "is_post_thumbnail" with value true into the entry, if it is the post_thumbnail
	 */
	function loadPostIdData($post_id) {
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_parent' => intval($post_id)
		);
		$images = get_posts($args);
		
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		if(!isset($post_thumbnail_id)) {
			$post_thumbnail_id = -1;
		}
		
		foreach($images as $key=>$value) {
			$mime = $value->post_mime_type;
			if(	$mime !='image/jpeg' AND $mime !='image/png') {
				unset($images[$key]);
			} elseif($value->ID==$post_thumbnail_id) {
				$images[$key]->is_post_thumbnail = true;
			}
		}
		return $images;
	}
	
	/**
	 * Checks if the thumb-image-dimensions are bigger than the actuall image.
	 * @param array thumbnail-data from the add_image_size-funtion (width, height)
	 * @param array original image-data-array (url, width, height)
	 * @return true if the original is smaller than the thumbnail-size
	 */
	function isLowRes($thumb,$orig) {
		if($thumb['width']>$orig[1] || $thumb['height']>$orig[2]) {
			return true;
		}
		return false;
	}
	
	function isUserPermitted() {
		$return = false;
		if(current_user_can('upload_files')) {
			$return = true;
		}
		//TODO maybe add noence (is it needed? there are no file- or db-operations)
		return $return;
	}
	
	/**
	 * For adding the "thickbox"-style in the mediathek 
	 */
	function adminHeaderCSS() {
		global $pagenow;
		if (   $pagenow == 'post.php'
			|| $pagenow == 'post-new.php'
			|| $pagenow == 'page.php' 
			|| $pagenow == 'page-new.php'
			|| $pagenow == 'upload.php') {
			wp_enqueue_style( 'thickbox' );
		}
	}
	
	
	/**
	 * For adding the "crop-thumbnail"-link on posts, pages and the mediathek
	 */
	function adminHeaderJS() {
		global $pagenow;
		if (   $pagenow == 'post.php'
			|| $pagenow == 'post-new.php'
			|| $pagenow == 'page.php' 
			|| $pagenow == 'page-new.php'
			|| $pagenow == 'upload.php') {
			
			wp_enqueue_script('thickbox', false, array('jquery','jquery-ui-tabs'));
			add_action('admin_footer',array($this,'cptAddLinksToAdmin'));
		}
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
	function gcd($a, $b) {
		if(function_exists('gmp_gcd')) {
			$gcd = gmp_strval(gmp_gcd($a,$b));
			$this->addDebug("gcd-version", "gmp_gcd:".$gcd);
			return ($gcd);
		} else {
			$gcd = $this->my_gcd($a,$b);
			$this->addDebug("gcd-version", "my_gcd:".$gcd);
			return $gcd;
		}
	}
	
	function my_gcd($a, $b) {
		$b = ( $a == 0 )? 0 : $b;
		return ( $a % $b )? $this->my_gcd($b, abs($a - $b)) : $b;
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
	
	
	/**
	 * adds the links into post-types and the media-library
	 */
	function cptAddLinksToAdmin() {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	var boxViewportHeight = $(window).height() - 100;
	//add link on posts and pages
	if ($('body.post-php, body.page-php, body.page-new.php, body.post-new-php').length > 0) {
		var post_id_hidden = $('form#post #post_ID');
		if (post_id_hidden) {

			post_id_hidden = parseInt(post_id_hidden.val());

			/** add link on top of editor **/
			$('#wp-content-media-buttons').append('<a style="margin:0 2em;" class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;post_id=' + post_id_hidden + '&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="<?php esc_attr_e('Crop Thumbnails',CROP_THUMBS_LANG) ?>"><?php esc_html_e('Crop Thumbnails',CROP_THUMBS_LANG); ?></a>');
			
			
			/** add link to featured image box **/
			var featuredImageLink = $('<a class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;image_by_post_id=' + post_id_hidden + '&amp;viewmode=single&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="<?php esc_attr_e('Crop Featured Image',CROP_THUMBS_LANG) ?>"><?php esc_html_e('Crop Featured Image',CROP_THUMBS_LANG); ?></a>')
				.css({'margin':'5px', 'padding':'5px','display':'inline-block','line-height':'1'})
				.addClass('button');
			$('#postimagediv .inside').after(featuredImageLink);
		}
	}

	/** add link on mediathek **/
	if ($('body.upload-php').length > 0) {
		$('#the-list tr').each(function() {
			if ($(this).find('td.media-icon img').attr('src').lastIndexOf("/wp-includes/images/") == -1) {
				var post_id = parseInt($(this).attr('id').substr(5));
				var last_span = $(this).find('.column-title .row-actions span:last-child');
				last_span.append(' | ');
				last_span.parent().append('<a class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;image_id=' + post_id + '&amp;viewmode=single&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="<?php esc_attr_e('Crop Thumbnail',CROP_THUMBS_LANG) ?>"><?php esc_html_e('Crop Thumbnail',CROP_THUMBS_LANG); ?></a>')
			}
		});
	}
});
</script>
<?php
	}
}



$cpte = new CropPostThumbnailsEditor();
?>