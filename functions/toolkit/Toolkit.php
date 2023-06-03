<?php
namespace crop_thumbnails\toolkit;

class Toolkit {
	public static function getAllImages() {
		global $wpdb;

		$query = 'SELECT p.ID, m.meta_value imagemeta, p.post_date, p.post_title, p.post_name 
			FROM '.$wpdb->prefix.'posts p
			LEFT JOIN '.$wpdb->prefix.'postmeta m ON p.ID = m.post_id
			WHERE
				p.post_type = "attachment"
				AND p.post_mime_type IN ("image/jpeg", "image/gif", "image/png", "image/webp")
				AND m.meta_key = "_wp_attachment_metadata"';

		$result = $wpdb->get_results( $wpdb->prepare($query, [ ]) );
		if(!empty($result)) foreach($result as $key=>$value) {
			$result[$key]->imagemeta = unserialize($value->imagemeta);
		}
		return $result;
	}

	public static function getAllPostThumbnails() {
		global $wpdb;

		$query = 'SELECT m.meta_value ID, p.ID parent
			FROM '.$wpdb->prefix.'posts p
			LEFT JOIN '.$wpdb->prefix.'postmeta m ON p.ID = m.post_id
			WHERE TRUE
				AND post_status = "publish"
				AND m.meta_key = "_thumbnail_id"';

		return $wpdb->get_results( $wpdb->prepare($query, [ ]) );
	}
	
}