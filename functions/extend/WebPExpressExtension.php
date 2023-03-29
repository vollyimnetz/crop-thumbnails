<?php
namespace crop_thumbnails\extend;

/**
 * ExtendWebpExpress is added for compatibility reasons.
 * Once the 
 * Plugin-URL: https://wordpress.org/support/plugin/webp-express/
 */
class WebPExpressExtension {

	public static function doExtend() {
		add_action('crop_thumbnails_after_save_new_thumb', [self::class, 'action_doConvert'], 100);
	}

	public static function action_doConvert($new_crop) {
		if(class_exists(\WebPExpress\Convert::class) && method_exists(\WebPExpress\Convert::class, 'convertFile')) {
			// convertFile internally loads the config of WebPExpress and converts the specified file.
			// we can can call this method because at 'crop_thumbnails_after_save_new_thumb' the new crop is already saved to uploads.
			$log = \WebPExpress\Convert::convertFile($new_crop);
			//error_log('WebPExpress\Convert::convertFile with the following Log -->'.print_r($log,true));
		}
	}
}

WebPExpressExtension::doExtend();