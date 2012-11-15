<?php

class CropPostThumbnailsEditor {
	
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
		$failure = false;
		if(!$this->isUserPermitted()) {
			$failure = true;
			
		}
		
		$data = null;
		switch(true) {
			case isset($_REQUEST['post_id'])://full programm
				$this->byPostId();
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
					$failure = true;
				}
				break;
			default:
				$failure = true;
				break; 
		}
		if($failure==false) {
			
		} else {
			_e('An error happend!',CPT_LANG);
		}
		die();//to prevent to send back a "0"
	}
	
	/**
	 * Display a list of images that are attached to this post_id.
	 * Hightlight the post-thumbnail (if it is attached to this post_id)
	 */
	function byPostId() {
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
			 $cptContent = '<div class="postTypeDisabledMsg">'.__('Cropping is disabled for this post-type.',CPT_LANG).'</div>';
		} elseif($data==false) {
			$cptContent = '<div class="listEmptyMsg">'.__('No images in this post yet. You have to upload some via upload dialog.',CPT_LANG).'</div>';
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
			<ul class="image-list">
			<?php
			$counter = 1;
			foreach($data as $key=>$image) : ?>
				<li class="entry cursor<?php echo (isset($image->is_post_thumbnail) ? ' post-thumbnail' : ''); ?>" rel="<?php echo $image->ID;?>">
					<h3><?php echo (isset($image->is_post_thumbnail) ? __('Post Thumbnail',CPT_LANG) : sprintf(__('Image %d',CPT_LANG),$counter));?></h3>
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
		wp_enqueue_style( 'cpt-window',plugins_url('css/cpt-window.css',dirname(__FILE__)),array('wp-admin'),CPT_VERSION);
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
		$image_obj = get_post(intval($_REQUEST['image_id']));
		
		//$post_id_attached holds the id of the post the image is attached to - can be null/empty
		$post_id_attached = -1;
		if(!empty($image_obj->post_parent)) {
			$post_id_attached=$image_obj->post_parent; 
		}
		
		//$current_parent_post_type
		$current_parent_post_type = '';
		$current_parent_post_id = -1;
		$_tmp = get_post(intval($_REQUEST['parent_post_id']));
		if(!empty($_REQUEST['parent_post_id']) && !empty($_tmp)) {
			$current_parent_post_type = $_tmp->post_type; 
			$current_parent_post_id = $_tmp->ID;
		}
		
		$all_image_sizes = $cptSettings->getImageSizes();
		$orig_img = wp_get_attachment_image_src($image_obj->ID, 'full'); 
		$cache_breaker = time();//a additional parameter that will be added to the image-urls to prevent the browser to show a cached image
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jcrop' );
		wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'cpt-crop',  plugins_url('js/cpt-crop.js',dirname(__FILE__)));
		
		wp_enqueue_style( 'cpt-window',plugins_url('css/cpt-window.css',dirname(__FILE__)),array('wp-admin'),CPT_VERSION);
		wp_enqueue_style( 'jcrop' );
		
		//the javascript
		ob_start(); ?>
<script>
jQuery(document).ready(function($) {
	cpt_lang = new Object();
	cpt_lang['bug'] = "<?php _e('bug - this case shouldnt be happend',CPT_LANG);?>";
	cpt_lang['wrongRatio'] = "<?php _e("Wrong ratio!<br \>OK - release images selected so far.<br \>Cancel - keep selected images so far.",CPT_LANG);?>";
	cpt_lang['selectOne'] = "<?php _e('First, select one image. Then, click once again.',CPT_LANG);?>";
	cpt_ajax_nonce = "<?php echo wp_create_nonce($cptSettings->getNonceBase()); ?>";
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
				<div class="postTypeDisabledMsg"><?php _e('Cropping is disabled for this post-type.',CPT_LANG); ?></div>
			</div>
		<?php else : ?>
		
		<div class="cpt-crop-view">
			<?php if($headline) :?><div class="header"><a class="back" href="<?php echo admin_url( 'admin-ajax.php'); ?>?action=croppostthumb_ajax&post_id=<?php echo $current_parent_post_id; ?>"><?php _e('back to image-list',CPT_LANG); ?></a></div><?php endif; ?>
			<div class="waitingWindow hidden"><?php _e('Please wait until the Images are cropped.',CPT_LANG); ?></div>
			<div class="mainWindow">
				<div class="selectionArea left">
					<h3><?php _e('Raw'); ?>: <?php echo $orig_img[1].' '.__('pixel',CPT_LANG)?>  x <?php echo $orig_img[2].' '.__('pixel',CPT_LANG) ?></h3>
					<img src="<?php echo $orig_img[0]?>" data-values='{"id":<?php echo $image_obj->ID; ?>,"parentId":<?php echo $post_id_attached ?>,"width":<?php echo $orig_img[1]?>,"height":<?php echo $orig_img[2] ?>}' />
					<button id="cpt-generate" class="button"><?php _e('save crop',CPT_LANG);?></button>
					<ul class="step-info">
						<li><?php _e('Step 1: Choose an image from the right.',CPT_LANG); ?></li>
						<li><?php _e('Step 2: Use the mouse change the size of the rectangle on the image above.',CPT_LANG); ?></li>
						<li><?php _e('Step 3: Click on "save crop".',CPT_LANG); ?></li>
						<li><?php _e('Hint: If you have one image selected, you can click on "select images with same ratio", to select all with the ratio of these image.',CPT_LANG); ?></li>
					</ul>
				</div>
				<div class="right">
					<button id="cpt-same-ratio" class="button"><?php _e('select images with same ratio',CPT_LANG)?></button>
					<button id="cpt-deselect" class="button"><?php _e('deselect all',CPT_LANG)?></button>
					<ul class="thumbnail-list">
						<?php 
						foreach($all_image_sizes as $img_size_name=>$value) :
							if(!$this->shouldSizeBeHidden($options,$img_size_name,$current_parent_post_type)) :
								$gcd = $this->gcd($value['width'],$value['height']);
								$print_ratio = $value['width']/$gcd.':'.$value['height']/$gcd;
								
								$print_cropped = '';
								$crop = 0;
								$_class= '';
								if(!empty($value['crop'])) {
									$print_cropped = ' ('.__('cropped',CPT_LANG).')';
									$crop = 1;
								} else {
									$_class = 'hidden';
								}
								$img_data = wp_get_attachment_image_src($image_obj->ID, $img_size_name);
								$ratio = ($value['width']/$gcd) / ($value['height']/$gcd);
							?>
							<li class="<?php echo $_class; ?>" rel="<?php echo $print_ratio; ?>">
								<strong><?php echo $img_size_name; ?></strong>
								<span class="dimensions"><?php _e('Dimensions:',CPT_LANG) ?> <?php echo $value['width'].' '.__('pixel',CPT_LANG)?> x <?php echo $value['height'].' '.__('pixel',CPT_LANG) ?> <?php echo $print_cropped ?></span>
								<span class="ratio"><?php _e('Ratio:',CPT_LANG) ?> <?php echo $print_ratio; ?></span>
								<img src="<?php echo $img_data[0]?>?<?php echo $cache_breaker ?>" data-values='{"name":"<?php echo $img_size_name; ?>","width":<?php echo $value['width']; ?>,"height":<?php echo $value['height']; ?>,"ratio":<?php echo $ratio ?>,"crop":<?php echo $crop ?>}' />
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
		//END the content
		
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
	function shouldSizeBeHidden($options,$img_size_name,$post_type='') {
		if(empty($post_type)) {
			return false;
		}
		
		if(empty($options['hide_size'][$post_type][$img_size_name])) {
			return false;
		}
		
		return true;
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
	
	function isUserPermitted() {
		$return = false;
		if(current_user_can('upload_files') && current_user_can('edit_pages')) {
			$return = true;
		}
		//TODO maybe add noence (is it needed? there are no file- or db-operations)
		return $return;
	}
	
	function adminHeaderCSS() {
		global $pagenow;
		if ( $pagenow == 'upload.php' ) {
			wp_enqueue_style( 'thickbox' );
		}
	}
	
	function adminHeaderJS() {
		global $pagenow;

		if (   $pagenow == 'post.php'
			|| $pagenow == 'post-new.php'
			|| $pagenow == 'page.php' 
			|| $pagenow == 'page-new.php'
			|| $pagenow == 'upload.php') {
			wp_enqueue_script('cpt-js', plugins_url( 'js/cpt-main.js', dirname(__FILE__) ), array('jquery','jquery-ui-tabs','thickbox'));
		}
	}
	
	/**
	 * Greatest cummon divisor
	 */
	function gcd($a, $b){
		$b = ( $a == 0 )? 0 : $b;
		return ( $a % $b )? $this->gcd($b, abs($a - $b)) : $b;
	}
}



$cpte = new CropPostThumbnailsEditor();
?>