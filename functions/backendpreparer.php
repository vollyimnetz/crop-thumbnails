<?php 
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
			wp_enqueue_style('crop-thumbnails-options-style',plugins_url('css/options.css',dirname(__FILE__)));
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
	
	/** add link on posts and pages **/
	if ($('body.post-php, body.page-php, body.page-new.php, body.post-new-php').length > 0) {
		var post_id_hidden = $('form#post #post_ID');
		if (post_id_hidden) {

			post_id_hidden = parseInt(post_id_hidden.val());

			/**
			 * add link on top of editor *
			 */
			var buttonContent = '';
			buttonContent+= '<a ';
				buttonContent+= 'class="button cropThumbnailBox"  href="#" data-cropthumbnail=\'{"post_id":'+ post_id_hidden +'}\' ';
				buttonContent+= 'title="<?php esc_attr_e('Crop Thumbnails',CROP_THUMBS_LANG) ?>"';
			buttonContent+= '>';
			buttonContent+= '<span class="dashicons dashicons-image-crop" style="color:#82878C;font-size: 14px;vertical-align: middle;"></span>';
			buttonContent+= '<?php esc_html_e('Crop Thumbnails',CROP_THUMBS_LANG); ?>';
			buttonContent+= '</a>';
			$('#wp-content-media-buttons').append(buttonContent);


			/**
			 * add link to featured image box *
			 */
			buttonContent = '';
			buttonContent+= '<a ';
			buttonContent+= 'class="button cropThumbnailBox" href="#" data-cropthumbnail=\'{"image_by_post_id":'+ post_id_hidden +',"viewmode":"single"}\' ';
			buttonContent+= 'title="<?php esc_attr_e('Crop Featured Image',CROP_THUMBS_LANG) ?>"';
			buttonContent+= '>';
			buttonContent+= '<span class="dashicons dashicons-image-crop" style="color:#82878C;font-size: 14px;vertical-align: middle;"></span>';
			buttonContent+= '<?php esc_html_e('Crop Featured Image',CROP_THUMBS_LANG); ?>';
			buttonContent+= '</a>';


			var featuredImageLink = $(buttonContent).css({'margin':'5px', 'padding':'5px','display':'inline-block','line-height':'1.2'});
			$('#postimagediv .inside').after(featuredImageLink);
			
			
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
				buttonContent+= '<a ';
				buttonContent+= 'class="cropThumbnailBox" href="#" data-cropthumbnail=\'{"image_id":'+ post_id +',"viewmode":"single"}\' ';
				buttonContent+= 'title="<?php esc_attr_e('Crop Featured Image',CROP_THUMBS_LANG) ?>"';
				buttonContent+= '>';
				buttonContent+= '<span class="dashicons dashicons-image-crop" style="color:#82878C;font-size: 14px;vertical-align: middle;"></span>';
				buttonContent+= '<?php esc_html_e('Crop Featured Image',CROP_THUMBS_LANG); ?>';
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
	 * Create Listener for click-events with element-class ".cropThumbnailBox".
	 * Open the modal box.
	 */
	$(document).on('click', '.cropThumbnailBox', function(e) {
		e.preventDefault();
		
		<?php
		/*****************************************************************************/
		/**
		 * Theme-Developers can adjust the size of the modal-dialog via filter.
		 */
		$modal_window_settings = array(
			'limitToWidth' => 800, //thats the maximum width the modal can be. On small screens it will be smaller (see offsets), set to FALSE if you want no limit
			'maxWidthOffset' => 50, //window-width minus "width_offset" equals modal-width
			'maxHeightOffset' => 100, //window-width minus "height_offset" equals modal-height
		);
		$modal_window_settings = apply_filters('crop_thumbnails_modal_window_settings',$modal_window_settings);
		
		$jsLimitOutput = '';
		if($modal_window_settings['limitToWidth']!==false) {
			$value = abs(intval($modal_window_settings['limitToWidth']));
			
			$jsLimitOutput.= 'if(boxViewportWidth>'.$value.') { boxViewportWidth = '.$value.'; }';
		}
		/*****************************************************************************/
		?>

		//modal-box dimensions (will not adjust on viewport change)
		var boxViewportHeight = $(window).height() - <?php echo abs(intval($modal_window_settings['maxHeightOffset'])); ?>;
		var boxViewportWidth = window.outerWidth - <?php echo abs(intval($modal_window_settings['maxWidthOffset'])); ?>;
		
		<?php echo $jsLimitOutput; ?>
	
		

		//get the data from the link
		var data = $(this).data('cropthumbnail');

		//construct the thickbox-parameter
		var url = ajaxurl+'?action=croppostthumb_ajax';
		for(var v in data) {
			url+='&amp;'+v+'='+data[v];
		}

		var content = $('<div><iframe src="'+url+'"></iframe></div>');
		var overlay;
		var isModalClassInitialSet = $('body').hasClass('modal-open');
		content.dialog({
				dialogClass : 'cropThumbnailModal',
				modal : true,
				title : $(this).attr('title'),
				resizable : false,
				draggable : false,
				autoOpen : false,
				closeOnEscape : true,
				height : boxViewportHeight,
				width : boxViewportWidth,
				close : function(event, ui ) {
					if(overlay!==undefined) {
						overlay.unbind('click');
					}

					//remove modal-open class (disable the scrollbars)
					if(!isModalClassInitialSet) {
						$('body').removeClass('modal-open');
					}
					$(this).dialog('destroy');
					
					/**
					 * We will trigger that the modal of the crop thumbnail is closed.
					 * So everyone that is up to, could build a cache-breaker on their images.
					 * HOW-TO cache-break:
					 * $('body').on('cropThumbnailModalClosed',function() {
					 *     CROP_THUMBNAILS_DO_CACHE_BREAK( $('.your-image-selector') );
					 * });
					 */
					$('body').trigger('cropThumbnailModalClosed');
				},
				open : function(event, ui) {
					overlay = $('.ui-widget-overlay.ui-front');
					overlay.addClass('cropThumbnailModalOverlay');
					overlay.click(function() {
						content.dialog('close');
					});

					//add body class (disable the scrollbars)
					$('body').addClass('modal-open');
				}
			})
			.dialog('open');
	});
});
</script>
<?php
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
			$html.= '<a class="button cropThumbnailBox" href="#" data-cropthumbnail=\'{"image_id":'.$post->ID.',"viewmode":"single"}\' ';
			$html.= 'title="'.esc_attr__('Crop Featured Image',CROP_THUMBS_LANG).'">';
			$html.= '<span class="dashicons dashicons-image-crop" style="color:#82878C;font-size: 14px;vertical-align: middle;"></span>'.esc_html__('Crop Featured Image',CROP_THUMBS_LANG);
			$html.= '</a>';

			$form_fields['cropthumbnails'] = array(
				'label' => '&nbsp;',
				'input' => 'html',
				'html' => $html
			);
		}
		return $form_fields;
	}
}
$cpt_postView = new CropPostThumbnailsBackendPreparer();
