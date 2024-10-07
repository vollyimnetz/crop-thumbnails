<?php
namespace crop_thumbnails\tests;
use \PHPUnit\Framework\TestCase;

define('TEST_PLUGIN_BASE',__DIR__.'/../../../..');

\WP_Mock::bootstrap();


/** END global wordpress functions **/
abstract class CptTestCase extends TestCase {

	public static $settingsMock;

	protected function get_CptSaveThumbnail() {
		include_once TEST_PLUGIN_BASE.'/functions/save.php';
		include_once TEST_PLUGIN_BASE.'/functions/helper.php';

		self::$settingsMock = \Mockery::mock('CropThumbnailsHelperInstance');
		self::$settingsMock->shouldReceive('getNonceBase')->andReturn('any');
		self::$settingsMock->shouldReceive('getOptions')->andReturn(['debug_data' => true]);

		$GLOBALS['CROP_THUMBNAILS_HELPER'] = self::$settingsMock;

		return new \crop_thumbnails\CptSaveThumbnail();
	}

	protected function get_CropPostThumbnailsEditor() {
		include_once TEST_PLUGIN_BASE.'/functions/helper.php';
		include_once TEST_PLUGIN_BASE.'/functions/save.php';
		include_once TEST_PLUGIN_BASE.'/functions/editor.php';

		self::$settingsMock = \Mockery::mock('CropThumbnailsHelperInstance');
		self::$settingsMock->shouldReceive('getNonceBase')->andReturn('any');
		self::$settingsMock->shouldReceive('getOptions')->andReturn(['debug_data' => true]);

		$GLOBALS['CROP_THUMBNAILS_HELPER'] = self::$settingsMock;

		return new \crop_thumbnails\CropPostThumbnailsEditor();
	}

	public function assertArrayEquals($array1, $array2, $rootPath = []) {
		foreach ($array1 as $key => $value) {
			$this->assertArrayHasKey($key, $array2);

			if (isset($array2[$key])) {
				$keyPath = $rootPath;
				$keyPath[] = $key;

				if (is_array($value)) {
					$this->assertArrayEquals($value, $array2[$key], $keyPath);
				} else {
					$this->assertEquals($value, $array2[$key], "Failed asserting that `".$array2[$key]."` matches expected `$value` for path `".implode(" > ", $keyPath)."`.");
				}
			}
		}
	}
}
