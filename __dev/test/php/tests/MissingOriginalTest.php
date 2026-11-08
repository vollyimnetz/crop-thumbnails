<?php
namespace crop_thumbnails\tests;
include_once __DIR__.'/CptTestCase.php';

/**
 * If the original of a scaled image is missing, the user has to confirm to crop from the smaller (scaled) image.
 */
class MissingOriginalTest extends CptTestCase {

	private $scaledFile;
	private $originalPath;

	public function setUp():void {
		\WP_Mock::setUp();
		$this->scaledFile = tempnam(sys_get_temp_dir(), 'cpt-scaled');//exists
		$this->originalPath = sys_get_temp_dir().'/cpt-original-that-does-not-exist.jpg';

		\WP_Mock::userFunction('wp_get_original_image_path', ['return' => function() { return $this->originalPath; }]);
		\WP_Mock::userFunction('get_attached_file', ['return' => function() { return $this->scaledFile; }]);
		\WP_Mock::userFunction('__', ['return' => function($p) { return $p; }]);
		$this->get_CptSaveThumbnail();
	}

	public function tearDown():void {
		if(file_exists($this->scaledFile)) unlink($this->scaledFile);
		\WP_Mock::tearDown();
	}

	/** @test **/
	public function original_missing_and_scaled_image_available() {
		$this->assertTrue(\crop_thumbnails\CptSaveThumbnail::isOriginalMissingButScaledImageAvailable(5));
	}

	/** @test **/
	public function not_the_fallback_case_if_the_original_exists() {
		$this->originalPath = $this->scaledFile.'-orig';
		file_put_contents($this->originalPath, 'x');
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isOriginalMissingButScaledImageAvailable(5));
		unlink($this->originalPath);
	}

	/** @test **/
	public function not_the_fallback_case_if_the_image_was_never_scaled() {
		$this->originalPath = $this->scaledFile;//WordPress returns the attached file if there is no original_image
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isOriginalMissingButScaledImageAvailable(5));
	}

	/** @test **/
	public function not_the_fallback_case_if_the_scaled_image_is_missing_too() {
		unlink($this->scaledFile);
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isOriginalMissingButScaledImageAvailable(5));
	}

	/** @test **/
	public function crop_source_is_the_original_if_it_exists() {
		$this->originalPath = $this->scaledFile.'-orig';
		file_put_contents($this->originalPath, 'x');
		$input = (object)['sourceImageId' => 5];
		$this->assertSame($this->originalPath, \crop_thumbnails\CptSaveThumbnail::getCropSourcePath($input));
		unlink($this->originalPath);
	}

	/** @test **/
	public function cropping_from_the_scaled_image_needs_the_confirmation() {
		$this->expectException(\Exception::class);
		\crop_thumbnails\CptSaveThumbnail::getCropSourcePath((object)['sourceImageId' => 5]);
	}

	/** @test **/
	public function a_confirmation_that_is_not_exactly_true_is_not_accepted() {
		foreach(['true', 1, 'yes', ['x']] as $value) {
			$thrown = false;
			try {
				\crop_thumbnails\CptSaveThumbnail::getCropSourcePath((object)['sourceImageId' => 5, 'acceptSmallerSource' => $value]);
			} catch(\Exception $e) {
				$thrown = true;
			}
			$this->assertTrue($thrown, 'value was accepted: '.json_encode($value));
		}
	}

	/** @test **/
	public function confirmed_crop_uses_the_scaled_image() {
		$input = (object)['sourceImageId' => 5, 'acceptSmallerSource' => true];
		$this->assertSame($this->scaledFile, \crop_thumbnails\CptSaveThumbnail::getCropSourcePath($input));
	}
}
