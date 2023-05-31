<?php
namespace crop_thumbnails;

/**
 * Adds the Crop-Thumbnail-Editor to the Backend.
 */
class CropPostThumbnailsBackendPreparer {
	
	protected $allowedMime = ['image/webp','image/jpeg','image/png'];
	
	public function __construct() {
		if ( is_admin() ) {
			//add style and javascript
			add_action( 'admin_print_styles', [&$this, 'adminHeaderCSS'] );
			add_action( 'admin_print_scripts', [&$this, 'adminHeaderJS'] );

			add_filter( 'attachment_fields_to_edit', [$this,'add_button_to_attachment_edit_view'], 10, 2 );
		}
	}

	/**
	 * Check if the crop-thumbnail-dialog should be available on the following pages. The default pages are
	 * page and post editing pages, as well as the media-library.
	 * 
	 * How to enhance the result.
	 * <code>
	 * add_filter('crop_thumbnails_activate_on_adminpages', function($oldValue) {
	 * 	global $pagenow;
	 * 	return $oldValue || $pagenow==='term.php';//for adding taxonomy edit-page to the list of pages where crop-thumbnails work
	 * });
	 * </code>
	 */
	protected function shouldCropThumbnailsBeActive() {
		global $pagenow;
		$options = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$result = ($pagenow == 'post.php'
			|| $pagenow == 'post-new.php'
			|| $pagenow == 'page.php'
			|| $pagenow == 'page-new.php'
			|| $pagenow == 'upload.php'
			|| !empty($options['include_js_on_all_admin_pages'])
			);
		$result = apply_filters('crop_thumbnails_activat_on_adminpages', $result);//leagacy filter with typo error
		$result = apply_filters('crop_thumbnails_activate_on_adminpages', $result);
		return $result;
	}
	
	/**
	 * For adding the styles in the backend
	 */
	public function adminHeaderCSS() {
		global $pagenow;
		if ($this->shouldCropThumbnailsBeActive()) {
			wp_enqueue_style('crop-thumbnails-options-style', plugins_url('app/main.css', __DIR__), [], CROP_THUMBNAILS_VERSION);
		}
	}
	
	/**
	 * For adding the "crop-thumbnail"-link on posts, pages and the mediathek
	 */
	function adminHeaderJS() {
		global $pagenow;
		if ($this->shouldCropThumbnailsBeActive()) {
			wp_enqueue_script( 'cpt_crop_editor', plugins_url('app/main.js', __DIR__), ['jquery','imagesloaded'], CROP_THUMBNAILS_VERSION);
			add_action('admin_footer', [$this,'addLinksToAdmin']);
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
			$html.= '<a class="button cropThumbnailsLink" href="#" data-cropthumbnail=\'{"image_id":'.$post->ID.',"viewmode":"single"}\' title="'.esc_attr__('Crop Featured Image','crop-thumbnails').'">';
			$html.= '<span class="wp-media-buttons-icon"></span> '.esc_html__('Crop Featured Image','crop-thumbnails');
			$html.= '</a>';

			$form_fields['cropthumbnails'] = [
				'label' => '&nbsp;',
				'input' => 'html',
				'html' => $html
			];
		}
		return $form_fields;
	}

	/**
	 * Check if on the current admin page (posttype) the crop-button should be visible in the featured image Box.
	 */
	protected static function showCropButtonOnThisAdminPage() {
		$screenData = get_current_screen();
		$settings = $GLOBALS['CROP_THUMBNAILS_HELPER']->getOptions();
		$showFeaturedImageCropButton = false;
		if(empty($settings['hide_post_type'][ $screenData->post_type ])) {
			$showFeaturedImageCropButton = true;
		}
		return $showFeaturedImageCropButton;
	}
	
	
	/**
	 * adds the links into post-types and the media-library
	 */
	function addLinksToAdmin() {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {

	/**
	 * Global accessable id of the current post (will be null if no post-element is present)
	 */
	CROP_THUMBNAILS_CURRENT_POST_ID = null;
	
	/**
	 * Adds a button to the featured image metabox (Wordpress < 5).
	 * The button will be visible only if a featured image is set.
	 */
	function handleFeaturedImageBox() {
		/**
		 * add link to featured image box
		 */
		const baseElem = $('#postimagediv');
		if(!baseElem.length) { return; }//this is not WordPress < 5
		
		let featuredImageLinkButton = '';
		featuredImageLinkButton+= '<p class="cropFeaturedImageWrap hidden">';
		featuredImageLinkButton+= '<a class="button cropThumbnailsLink" href="#" data-cropthumbnail=\'{"image_id":'+ parseInt(wp.media.featuredImage.get()) +',"viewmode":"single","posttype":"<?php echo get_post_type(); ?>"}\' title="<?php esc_attr_e('Crop Featured Image','crop-thumbnails') ?>">';
		featuredImageLinkButton+= '<span class="wp-media-buttons-icon"></span> <?php esc_html_e('Crop Featured Image','crop-thumbnails'); ?>';
		featuredImageLinkButton+= '</a>';
		featuredImageLinkButton+= '</p>';
		baseElem.find('.inside').after( $(featuredImageLinkButton) );
		
		
		function updateCropFeaturedImageButton(currentId) {
			const wrap = baseElem.find('.cropFeaturedImageWrap');
			
			if(currentId===-1) {
				wrap.addClass('hidden');
			} else {
				wrap.removeClass('hidden');
			}
			const link = wrap.find('a');
			const data = JSON.parse( link[0].dataset.cropthumbnail );;
			data.image_id = currentId;
			link.attr('data-cropthumbnail', JSON.stringify(data));
		}
		
		wp.media.featuredImage.frame().on( 'select', function(){
			updateCropFeaturedImageButton( parseInt(wp.media.featuredImage.get()) );
		});
		
		baseElem.on('click', '#remove-post-thumbnail', function(){
			updateCropFeaturedImageButton(-1);
		});
		
		updateCropFeaturedImageButton( parseInt(wp.media.featuredImage.get()) );
	}

	/**
	 * Adds a button to the featured image panel (Wordpress >= 5)
	 * 
	 * I know this way to add the button is quite dirty - will refactoring it when i learned the api-way to do it.
	 */
	function handleFeaturedImagePanel() {
		// @see https://github.com/WordPress/gutenberg/tree/master/packages/editor/src/components/post-featured-image
		
		if(typeof wp.element === 'undefined') { return };//this is not WordPress 5.x

		var el = wp.element.createElement;
		function wrapPostFeaturedImage( OriginalComponent ) { 
			return function( props ) {
				setTimeout(function() {
					var baseElem = $('.edit-post-sidebar');
					var cropButton = $('<button class="button cropThumbnailsLink" style="margin-top:1em" data-cropthumbnail=\'{"image_id":'+ parseInt(props.featuredImageId) +',"viewmode":"single","posttype":"<?php echo get_post_type(); ?>"}\' title="<?php esc_attr_e('Crop Featured Image','crop-thumbnails') ?>"><span class="wp-media-buttons-icon"></span> <?php esc_html_e('Crop Featured Image','crop-thumbnails'); ?></button>');
					if(typeof props.media !== 'undefined') {
						var panel = baseElem.find('.editor-post-featured-image');
						panel.find('.cropThumbnailsLink').remove();
						if(panel.find('.components-responsive-wrapper').length>0) {
							panel.append(cropButton);
						}
					}
				}, 50);
				
				return (
					el(
						wp.element.Fragment,
						{},
						el(
							OriginalComponent,
							props
						),
						//TODO add in a better way here
					)
				);
			} 
		}
		if(typeof wp.hooks !== 'undefined' && typeof wp.hooks.addFilter !== 'undefined') {
			wp.hooks.addFilter(
				'editor.PostFeaturedImage',
				'crop-thumbnails/wrap-post-featured-image',
				wrapPostFeaturedImage
			);
		}	
	}
	
	<?php if(self::showCropButtonOnThisAdminPage()) : ?>
	/** add link on posts and pages **/
	if ($('body.post-php, body.page-php, body.page-new.php, body.post-new-php').length > 0) {
		var post_id_hidden = $('form#post #post_ID');
		if (post_id_hidden) {

			post_id_hidden = parseInt(post_id_hidden.val());
			CROP_THUMBNAILS_CURRENT_POST_ID = post_id_hidden;

			handleFeaturedImageBox();
			handleFeaturedImagePanel();
			
			$('body').on('cropThumbnailModalClosed',function() {
				//lets cache-break the crop-thumbnail-preview-box
				CROP_THUMBNAILS_DO_CACHE_BREAK($('#postimagediv img'));
			});
		}
	}
	<?php endif; ?>

	/** add link on mediathek **/
	if ($('body.upload-php').length > 0) {
		$('#the-list tr').each(function() {
			if ($(this).find('td span.media-icon').hasClass('image-icon')) {
				var post_id = parseInt($(this).attr('id').substr(5));
				var last_span = $(this).find('.column-title .row-actions > span:last-child');
				last_span.append(' | ');

				var buttonContent = '';
				buttonContent+= '<span class="cropThumbnailsLinkWrap">';
				buttonContent+= '<a class="cropThumbnailsLink" href="#" data-cropthumbnail=\'{"image_id":'+ post_id +',"viewmode":"single"}\' title="<?php esc_attr_e('Crop Featured Image','crop-thumbnails') ?>">';
				buttonContent+= '<span class="wp-media-buttons-icon"></span> <?php esc_html_e('Crop Featured Image','crop-thumbnails'); ?>';
				buttonContent+= '</a>';
				buttonContent+= '</span>';


				last_span.parent().append( buttonContent);
			}
		});
		$('body').on('cropThumbnailModalClosed',function() {
			//lets cache-break the crop-thumbnail-preview-box
			CROP_THUMBNAILS_DO_CACHE_BREAK($('#the-list tr .media-icon img'));
		});
	}
});
</script>
<?php
	}
}
$cpt_postView = new CropPostThumbnailsBackendPreparer();
