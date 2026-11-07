<?php
/**
 * Test helper: runs the plugin's default callbacks for filters that are registered on "after_setup_theme" in production
 * (WP_Mock does not execute callbacks added with add_filter, and "after_setup_theme" never fires in unit tests).
 *
 * PHP resolves unqualified function calls inside the namespace "crop_thumbnails" to this function first;
 * everything that is not explicitly handled is passed on to the (WP_Mock) global apply_filters().
 */
namespace crop_thumbnails;

function apply_filters($tag, $value, ...$args) {
	if($tag === 'crop_thumbnails_do_crop' && !empty($GLOBALS['CPT_TEST_RUN_DEFAULT_CROP_FILTER'])) {
		return CptSaveThumbnail::filter_doWpCrop($value, ...$args);
	}
	if($tag === 'crop_thumbnails_should_delete_old_file' && !empty($GLOBALS['CPT_TEST_RUN_DEFAULT_CROP_FILTER'])) {
		return CptSaveThumbnail::filter_shouldDeleteOldFile($value, ...$args);
	}
	return \apply_filters($tag, $value, ...$args);
}
