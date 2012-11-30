<?php
add_action('add_meta_boxes', 'cptChangeFeaturedImageMetaBox');
function cptChangeFeaturedImageMetaBox($post_type) {
	if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) ) {
		remove_meta_box('postimagediv', null, 'side');
		add_meta_box('postimagediv', __('Featured Image'), 'cptFeaturedImageMetaBox', null, 'side', 'low');
	}	
}


function cptFeaturedImageMetaBox($post) {
	/**
	 * Display post thumbnail meta box.
	 *
	 * @since 2.9.0
	 */
//function post_thumbnail_meta_box( $post ) {
	global $_wp_additional_image_sizes;

	?><script type="text/javascript">
	jQuery( function($) {
		var $element     = $('#select-featured-image'),
			$thumbnailId = $element.find('input[name="thumbnail_id"]'),
			title        = '<?php _e( "Choose a Featured Image" ); ?>',
			update       = '<?php _e( "Update Featured Image" ); ?>',
			Attachment   = wp.media.model.Attachment,
			frame, setFeaturedImage;

		setFeaturedImage = function( thumbnailId ) {
			var selection;

			$element.find('img').remove();
			$element.toggleClass( 'has-featured-image', -1 != thumbnailId );
			$thumbnailId.val( thumbnailId );

			if ( frame ) {
				selection = frame.get('library').get('selection');

				if ( -1 === thumbnailId )
					selection.clear();
				else
					selection.add( Attachment.get( thumbnailId ) );
			}
			/** plugin: crop-thumbnail code **/
			cptCheck_SetFeaturedImage();
			/** END plugin: crop-thumbnail code **/
		};

		$element.on( 'click', '.choose, img', function( event ) {
			var options, thumbnailId;

			event.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			options = {
				title:   title,
				library: {
					type: 'image'
				}
			};

			thumbnailId = $thumbnailId.val();
			if ( '' !== thumbnailId && -1 !== thumbnailId )
				options.selection = [ Attachment.get( thumbnailId ) ];

			frame = wp.media( options );

			frame.toolbar.on( 'activate:select', function() {
				frame.toolbar.view().set({
					select: {
						style: 'primary',
						text:  update,

						click: function() {
							var selection = frame.state().get('selection'),
								model = selection.first(),
								sizes = model.get('sizes'),
								size;

							setFeaturedImage( model.id );

							// @todo: might need a size hierarchy equivalent.
							if ( sizes )
								size = sizes['post-thumbnail'] || sizes.medium;

							// @todo: Need a better way of accessing full size
							// data besides just calling toJSON().
							size = size || model.toJSON();

							frame.close();

							$( '<img />', {
								src:    size.url,
								width:  size.width
							}).prependTo( $element );
						}
					}
				});
			});
		});

		$element.on( 'click', '.remove', function( event ) {
			event.preventDefault();
			setFeaturedImage( -1 );
		});
		
		cptCheck_SetFeaturedImage();
	});
	</script>

	<?php
	$thumbnail_id   = get_post_meta( $post->ID, '_thumbnail_id', true );
	$thumbnail_size = isset( $_wp_additional_image_sizes['post-thumbnail'] ) ? 'post-thumbnail' : 'medium';
	$thumbnail_html = wp_get_attachment_image( $thumbnail_id, $thumbnail_size );

	$classes = empty( $thumbnail_id ) ? '' : 'has-featured-image';

	?><div id="select-featured-image"
		class="<?php echo esc_attr( $classes ); ?>"
		data-post-id="<?php echo esc_attr( $post->ID ); ?>">
		<?php echo $thumbnail_html; ?>
		<input type="hidden" name="thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
		<a href="#" class="choose button-secondary"><?php _e( 'Choose a Featured Image' ); ?></a>
		<a href="#" class="remove"><?php _e( 'Remove Featured Image' ); ?></a>
	</div>
	<?php
}
?>