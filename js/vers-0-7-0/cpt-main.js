/* functionality till version 0.7.0 */
jQuery(document).ready(function($) {
	var boxViewportHeight = $(window).height() - 100;
	//add link on posts and pages
	if ($('body.post-php, body.page-php, body.page-new.php, body.post-new-php').length > 0) {
		var post_id_hidden = $('form#post #post_ID');
		if (post_id_hidden) {

			post_id_hidden = parseInt(post_id_hidden.val());

			/** add link on top of editor **/
			$('#wp-content-media-buttons').append('<a style="margin:0 2em;" class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;post_id=' + post_id_hidden + '&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="Crop-Thumbnails">Crop-Thumbnails</a>');
			

			/** add link to meta-box for post-thumbnails on posts and pages **/
			/* static way: after reload of the page */
			$('#remove-post-thumbnail').parent().parent().append('<a class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;image_by_post_id=' + post_id_hidden + '&amp;viewmode=single&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="Crop-Thumbnail">Crop-Thumbnail</a>');
			
			/* dynamic way: after ajax change of the post thumbnail
			 * extend the functionality of the base function "WPSetThumbnailHTML"
			 */
			if ( typeof WPSetThumbnailHTML === 'function') {
				var cptWPSetThumbnailHTMLoriginal = WPSetThumbnailHTML;
				WPSetThumbnailHTML = function(html) {
					cptWPSetThumbnailHTMLoriginal(html);
					$('#remove-post-thumbnail').parent().parent().append('<a class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;image_by_post_id=' + post_id_hidden + '&amp;viewmode=single&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="Crop-Thumbnail">Crop-Thumbnail</a>');
				}
			}
			/** END add link to meta-box for post-thumbnails on posts and pages **/
		}
	}

	/** add link on mediathek **/
	if ($('body.upload-php').length > 0) {

		$('#the-list tr').each(function() {
			if ($(this).find('td.media-icon img').attr('src').lastIndexOf("/wp-includes/images/") == -1) {
				var post_id = parseInt($(this).attr('id').substr(5));
				var last_span = $(this).find('.column-title .row-actions span:last-child');
				last_span.append(' | ');
				last_span.parent().append('<a class="thickbox" href="' + ajaxurl + '?action=croppostthumb_ajax&amp;image_id=' + post_id + '&amp;viewmode=single&amp;TB_iframe=1&amp;width=800&amp;height=' + boxViewportHeight + '" title="Crop-Thumbnail">Crop-Thumbnail</a>')
			}
		});
	}
});
