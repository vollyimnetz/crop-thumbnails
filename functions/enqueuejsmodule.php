<?php
namespace crop_thumbnails;

if(!function_exists('wp_enqueue_script_module')) {
	//add type="module" to certain scripts
	add_filter('script_loader_tag', function($tag, $handle, $src) {
		$moduleScripts = ['crop-thumbnails-options-js', 'cpt_crop_editor'];
		if( !in_array($handle, $moduleScripts) ) return $tag;

		if(strpos($tag,'text/javascript') > -1) {
			return str_replace([" type='text/javascript' ", "type=\"text/javascript\" "], " type='module' ", $tag);
		} else {
			return str_replace("<script ", "<script type='module' ", $tag);
		}
	}, 10, 3);
}
