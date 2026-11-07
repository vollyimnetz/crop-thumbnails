<?php
namespace crop_thumbnails\tests;
include_once __DIR__.'/CptTestCase.php';

class FilenameAndDeleteTest extends CptTestCase {

	public function setUp():void {
		\WP_Mock::setUp();
		\WP_Mock::userFunction('__', ['return' => function($p) { return $p; }]);
		\WP_Mock::userFunction('wp_basename', ['return' => function($path, $suffix = '') {
			return urldecode(basename(str_replace(['%2F', '%5C'], '/', urlencode($path)), $suffix));
		}]);
		\WP_Mock::userFunction('wp_check_filetype', ['return' => function($file) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			return ['ext' => $ext, 'type' => $ext === 'png' ? 'image/png' : 'image/jpeg'];
		}]);
		\WP_Mock::userFunction('wp_get_mime_types', ['return' => [
			'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'
		]]);
	}

	public function tearDown():void {
		\WP_Mock::tearDown();
	}

	private function call($method, ...$args) {
		$this->get_CptSaveThumbnail();
		$m = new \ReflectionMethod(\crop_thumbnails\CptSaveThumbnail::class, $method);
		return $m->invoke(null, ...$args);
	}


	/** @test **/
	public function filename_without_output_mapping_is_unchanged() {
		\WP_Mock::onFilter('image_editor_output_format')->with([], '/up/photo.jpg', 'image/jpeg')->reply([]);
		$this->assertEquals('/up/photo-150x150.jpg', $this->call('generateFilename', '/up/photo.jpg', [], 150, 150, true));
	}

	/** @test **/
	public function filename_with_webp_mapping_does_not_keep_source_extension() {
		\WP_Mock::onFilter('image_editor_output_format')->with([], '/up/photo.jpg', 'image/jpeg')->reply(['image/jpeg' => 'image/webp']);
		$this->assertEquals('/up/photo-150x150.webp', $this->call('generateFilename', '/up/photo.jpg', [], 150, 150, true));
	}

	/** @test **/
	public function filename_with_mapping_uses_original_image_name() {
		$meta = ['original_image' => 'photo.jpg'];
		\WP_Mock::onFilter('image_editor_output_format')->with([], '/up/photo-scaled.jpg', 'image/jpeg')->reply(['image/jpeg' => 'image/webp']);
		$this->assertEquals('/up/photo-150x150.webp', $this->call('generateFilename', '/up/photo-scaled.jpg', $meta, 150, 150, true));
	}

	/** @test **/
	public function shared_file_is_kept_but_unused_file_is_deleted() {
		$meta = ['file' => '2024/01/photo.jpg', 'sizes' => [
			'square' => ['file' => 'photo.jpg-1024x1024.webp'],
			'large_square' => ['file' => 'photo-1024x1024.jpg'],
		]];
		\WP_Mock::userFunction('wp_delete_file', ['times' => 1, 'args' => ['/up/photo-old.jpg']]);
		$this->call('deleteUnusedFiles', ['/up/photo-1024x1024.jpg', '/up/photo-old.jpg'], $meta);
		$this->assertTrue(true);
	}
}
