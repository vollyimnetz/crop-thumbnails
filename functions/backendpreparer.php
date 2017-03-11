<?php

/**
 * Adds the Crop-Thumbnail-Editor to the Backend.
 */
class CropPostThumbnailsBackendPreparer {
	
	private $allowedMime = array('image/jpeg','image/png');
	
	function __construct() {
		if ( is_admin() ) {
			//add style and javascript
			add_action( 'admin_print_styles', array(&$this, 'adminHeaderCSS') );
			add_action( 'admin_print_scripts', array(&$this, 'adminHeaderJS') );

			add_filter( 'attachment_fields_to_edit', array($this,'add_button_to_attachment_edit_view'), 10, 2 );
		}
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
			wp_enqueue_style("wp-jquery-ui-dialog");
			wp_enqueue_style('crop-thumbnails-options-style',plugins_url('css/cpt-backend.css',dirname(__FILE__)));
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

			wp_enqueue_script('jquery-ui-dialog');
			add_action('admin_footer',array($this,'addLinksToAdmin'));
		}
	}
	
	
	/**
	 * Add an field to the attachment edit dialog
	 * @see http://code.tutsplus.com/tutorials/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 * @see https://make.wordpress.org/core/2012/12/12/attachment-editing-now-with-full-post-edit-ui/
	 * @param array $form_fields
	 * @param object $post
	 */
	public function add_button_to_attachment_edit_view( $form_fields, $post ) {

		if(in_array($post->post_mime_type,$this->allowedMime)) {
			$html = '';
			$html.= '<a class="button cropThumbnailsLink" href="#" data-cropthumbnail=\'{"image_id":'.$post->ID.',"viewmode":"single"}\' title="'.esc_attr__('Crop Featured Image',CROP_THUMBS_LANG).'">';
			$html.= '<span class="wp-media-buttons-icon"></span> '.esc_html__('Crop Featured Image',CROP_THUMBS_LANG);
			$html.= '</a>';

			$form_fields['cropthumbnails'] = array(
				'label' => '&nbsp;',
				'input' => 'html',
				'html' => $html
			);
		}
		return $form_fields;
	}
	
	
	/**
	 * adds the links into post-types and the media-library
	 */
	function addLinksToAdmin() {

?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	/**
	 * Provide a global accessable cache-break-function (only available on backend-pages where crop-thumbnail is active --> post-editor, mediathek)
	 * Calling this function will add a timestamp on the provided Image-Element.
	 * ATTENTION: using this will also delete all other parameters on the images src-attribute.
	 * @param {dom-element / jquery-selection} elem
	 */
	CROP_THUMBNAILS_DO_CACHE_BREAK = function(elem) {
		var images = $(elem);
		for(var i = 0; i<images.length; i++) {
			var img = $(images[i]);//select image
			var imageUrl = img.attr('src');
			var imageUrlArray = imageUrl.split("?");
			
			img.attr('src',imageUrlArray[0]+'?&cacheBreak='+(new Date()).getTime());
		}
	};
	
	/**
	 * Adds a button to the featured image metabox.
	 * The button will be visible only if a featured image is set.
	 */
	function handleFeaturedImageBox() {
		/**
		 * add link to featured image box
		 */
		var baseElem = $('#postimagediv');
		if(!baseElem.length) {
			return;
		}
		
		var featuredImageLinkButton = '';
		featuredImageLinkButton+= '<p class="cropFeaturedImageWrap hidden">';
		featuredImageLinkButton+= '<a class="button cropThumbnailsLink" href="#" data-cropthumbnail=\'{"image_id":'+ parseInt(wp.media.featuredImage.get()) +',"viewmode":"single","posttype":"<?php echo get_post_type(); ?>"}\' title="<?php esc_attr_e('Crop Featured Image',CROP_THUMBS_LANG) ?>">';
		featuredImageLinkButton+= '<span class="wp-media-buttons-icon"></span> <?php esc_html_e('Crop Featured Image',CROP_THUMBS_LANG); ?>';
		featuredImageLinkButton+= '</a>';
		baseElem.find('.inside').after( $(featuredImageLinkButton) );
		
		
		function updateCropFeaturedImageButton(currentId) {
			var wrap = baseElem.find('.cropFeaturedImageWrap');
			
			if(currentId===-1) {
				wrap.addClass('hidden');
			} else {
				wrap.removeClass('hidden');
			}
			var link = wrap.find('a');
			var data = link.data('cropthumbnail');
			data.image_id = currentId;
			link.data('cropthumbnail',data);
		}
		
		wp.media.featuredImage.frame().on( 'select', function(){
			updateCropFeaturedImageButton( parseInt(wp.media.featuredImage.get()) );
		});
		
		baseElem.on('click', '#remove-post-thumbnail', function(){
			updateCropFeaturedImageButton(-1);
		});
		
		updateCropFeaturedImageButton( parseInt(wp.media.featuredImage.get()) );
	}
	
	/** add link on posts and pages **/
	if ($('body.post-php, body.page-php, body.page-new.php, body.post-new-php').length > 0) {
		var post_id_hidden = $('form#post #post_ID');
		if (post_id_hidden) {

			post_id_hidden = parseInt(post_id_hidden.val());

			handleFeaturedImageBox();
			
			$('body').on('cropThumbnailModalClosed',function() {
				//lets cache-break the crop-thumbnail-preview-box
				CROP_THUMBNAILS_DO_CACHE_BREAK($('#postimagediv img'));
			});
		}
	}

	/** add link on mediathek **/
	if ($('body.upload-php').length > 0) {
		$('#the-list tr').each(function() {
			if ($(this).find('td span.media-icon').hasClass('image-icon')) {
				var post_id = parseInt($(this).attr('id').substr(5));
				var last_span = $(this).find('.column-title .row-actions span:last-child');
				last_span.append(' | ');

				var buttonContent = '';
				buttonContent+= '<a class="cropThumbnailsLink" href="#" data-cropthumbnail=\'{"image_id":'+ post_id +',"viewmode":"single"}\' title="<?php esc_attr_e('Crop Featured Image',CROP_THUMBS_LANG) ?>">';
				buttonContent+= '<span class="wp-media-buttons-icon"></span> <?php esc_html_e('Crop Featured Image',CROP_THUMBS_LANG); ?>';
				buttonContent+= '</a>';


				last_span.parent().append( buttonContent);
			}
		});
		$('body').on('cropThumbnailModalClosed',function() {
			//lets cache-break the crop-thumbnail-preview-box
			CROP_THUMBNAILS_DO_CACHE_BREAK($('#the-list tr .media-icon img'));
		});
	}

	/**
	 * Create Listener for click-events with element-class ".cropThumbnailsLink".
	 * Open the modal box.
	 */
	$(document).on('click', '.cropThumbnailsLink', function(e) {
		e.preventDefault();
		

		//get the data from the link
		var data = $(this).data('cropthumbnail');

		//construct the thickbox-parameter
		var url = ajaxurl+'?action=croppostthumb_ajax';
		for(var v in data) {
			url+='&amp;'+v+'='+data[v];
		}
		var title = $(this).attr('title');
		
		
		var CPT_Modal = function() {
			var that = this;
			
			function removeModal() {
				$('#cpt_Modal .cpt_ModalClose, #cpt_Modal').unbind('click');
				$('#cpt_Modal').remove();
				$('body').removeClass('cpt_ModalIsOpen');
			}
			
			/**
			 * Should be called when the close-button is clicked.
			 * Will trigger the "cropThumbnailModalClosed"-event to the body on close,
			 * so everyone that is up to, could build a cache-breaker on their images.
			 * HOW-TO cache-break:
			 * $('body').on('cropThumbnailModalClosed',function() {
			 *     CROP_THUMBNAILS_DO_CACHE_BREAK( $('.your-image-selector') );
			 * });
			 * @var Event
			 */
			that.close = function(event) {
				removeModal();
				$('body').trigger('cropThumbnailModalClosed');
			};
			
			/**
			 * Should be called when the background is clicked
			 * @var Event
			 */
			that.closeByBackground = function(event) {
				if(event.target==document.getElementById('cpt_Modal')) {
					that.close(event);
				}
			};
			
			that.open = function(url,title) {
				var modalHtml = '';
				modalHtml+= '<div id="cpt_Modal" class="cpt_Modal">';
				modalHtml+= '<div class="cpt_ModalDialog">';
				modalHtml+= '<div class="cpt_ModalHeader"><div class="cpt_ModalTitle">'+title+'</div><span class="cpt_ModalClose">&times;</span></div>';
				
				modalHtml+= '<div class="cpt_ModalContent">';
				modalHtml+= '<div class="cpt_IframeScrollFrame">';
				modalHtml+= '<iframe src="'+url+'"></iframe>';
				modalHtml+= '</div>';//end cpt_IframeScrollFrame
				modalHtml+= '</div>';//end cpt_ModalContent
				modalHtml+= '</div>';//end cpt_ModalDialog
				modalHtml+= '</div>';//end cpt_Modal;
				
				
				$('body').prepend(modalHtml).addClass('cpt_ModalIsOpen');
				$('#cpt_Modal .cpt_ModalClose').click(that.close);
				$('#cpt_Modal').click(that.closeByBackground);
			};
		};
		
		var modal = new CPT_Modal();
		modal.open(url,title);
	});
});
</script>
<?php
	}
}
$cpt_postView = new CropPostThumbnailsBackendPreparer();
