<?php
define('TEST_PLUGIN_BASE',__DIR__.'/../../../..');

\WP_Mock::bootstrap();

define('CROP_THUMBS_LANG','cpt_lang');

/** END global wordpress functions **/
abstract class TestCase extends PHPUnit_Framework_TestCase {
	
	protected function getTestObject() {
		include_once TEST_PLUGIN_BASE.'/functions/save.php';
		include_once TEST_PLUGIN_BASE.'/functions/settings.php';
		
		$settingsMock = Mockery::mock('CropThumbnailsSettings');
		$settingsMock->shouldReceive('getImageSizes')->andReturn([]);
		$settingsMock->shouldReceive('getNonceBase')->andReturn('any');
		$GLOBALS['cptSettings'] = $settingsMock;
		
		return new CptSaveThumbnail();
	}
}
