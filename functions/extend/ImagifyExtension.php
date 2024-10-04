<?php
namespace crop_thumbnails\extend;

/**
 * Imagify is added for compatibility reasons.
 * This class will trigger the Imagify optimization when a new thumbnail is created. 
 * Plugin-URL: https://en-gb.wordpress.org/plugins/imagify/
 * 
 * @author Aaron Summers
 * @link https://github.com/aaronsummers
 * @version 1.0.0
 * @since 1.8.0
 * @package crop-thumbnails
 */
class ImagifyExtension {

	/**
	 * Extends the functionality of the Imagify plugin by adding a custom action.
	 *
	 * This method hooks into the 'plugins_loaded' action to check if the 
	 * Imagify_Auto_Optimization class and its 'do_auto_optimization' method exist.
	 * If they do, it adds another action 'crop_thumbnails_after_save_new_thumb' 
	 * which triggers the 'actionDoWebPConvertion' method after a new thumbnail 
	 * is created. The hook provides the file path of the new thumbnail.
	 *
	 * @return void
	 */
	 public static function doExtend() {

		 add_action('plugins_loaded', function() {

			 if ( class_exists( Imagify_Auto_Optimization::class) && method_exists( Imagify_Auto_Optimization::class, 'do_auto_optimization' ) ) {
				// crop_thumbnails_after_save_new_thumb is a hook that is triggered after a new thumbnail is created amd returns the file path of the new thumbnail (not the URL)
				add_action( 'crop_thumbnails_after_save_new_thumb', array( self::class, 'actionDoWebPConvertion' ), 100, 1 );
			}
		});

	 }
 

	/**
	 * Converts an image to WebP format using Imagify.
	 *
	 * This method attempts to optimize an image file by converting it to WebP format.
	 * It uses the Imagify_Auto_Optimization class to perform the optimization.
	 *
	 * @param string $image_file_path The file path of the image to be converted.
	 *
	 * @return void
	 */
	 public static function actionDoWebPConvertion( $image_file_path ) {

		$imagify       = new Imagify_Auto_Optimization();
		$sourceImageId = self::getImageIdByFilePath( $image_file_path );

		if ( $sourceImageId ) {
			$imagify->do_auto_optimization( $sourceImageId, $is_new_image = false );
		} else {
			error_log( 'class ImagifyExtension $sourceImageId is empty. Unable to run imagify do_auto_optimization()' );
		}
	 }
 
	/**
	 * Retrieves the attachment ID based on the given file path.
	 *
	 * This function converts the absolute file path to a relative path,
	 * removes the size from the file name, and then queries for the attachment ID.
	 *
	 * @param string $file_path The absolute file path of the image.
	 * @return int|null The attachment ID if found, or null if not found.
	 */
	 private static function getImageIdByFilePath( $file_path ) {

		 // Get the upload directory
		 $upload_dir = wp_upload_dir();
		 // Convert the file path to a relative path
		 $relative_path = str_replace( $upload_dir['basedir'] . '/', '', $file_path );
		 // Remove the size from the file name
		 $relative_path_no_size = preg_replace( '/-\d+x\d+(?=\.\w+$)/', '', $relative_path );
		 // Get the attachment ID
		 $attachment_id = self::attachmentQuery( $relative_path ) ?? self::attachmentQuery( $relative_path_no_size );
 
		 return $attachment_id;

	 }
 
	/**
	 * Retrieves the attachment ID based on the relative image path.
	 *
	 * This function queries the WordPress database to find the ID of an attachment
	 * whose GUID matches the provided relative path.
	 *
	 * @param string $relative_path The relative path of the image.
	 * @return int|null The attachment ID if found, null otherwise.
	 */
	 private static function attachmentQuery( $relative_path ) {

		global $wpdb;
		// Get the image ID by the relative image path
		$query = $wpdb->prepare( "
			SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND guid LIKE %s
			LIMIT 1
		", '%' . $wpdb->esc_like( $relative_path ) );
 
		 return $wpdb->get_var( $query );

	 }
}
 
ImagifyExtension::doExtend();