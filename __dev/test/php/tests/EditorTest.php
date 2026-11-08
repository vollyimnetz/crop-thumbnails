<?php
namespace crop_thumbnails\tests;
include_once __DIR__.'/CptTestCase.php';

class EditorTest extends CptTestCase {

	static $testObj;

	public function setUp():void {
		\WP_Mock::setUp();

		\WP_Mock::userFunction( '__',['return' => function($param) { return $param; } ]);

		self::$testObj = $this->get_CropPostThumbnailsEditor();

	}

	public function tearDown():void {
		self::$testObj = null;
		\WP_Mock::tearDown();
	}

	public function test_todo() {
		$this->assertTrue(true,'TODO');
	}

	protected function callProtected($method, ...$args) {
		$m = new \ReflectionMethod(self::$testObj, $method);
		return $m->invoke(self::$testObj, ...$args);
	}

	/**
	 * @dataProvider invalidDimensionsProvider
	 */
	public function test_gcd_never_fails_on_invalid_dimensions($a, $b) {
		//used to throw a DivisionByZeroError on PHP >= 8
		$this->assertSame(1, $this->callProtected('gcd', $a, $b));
	}

	public static function invalidDimensionsProvider() {
		return [
			'null, null (unreadable image)' => [null, null],
			'zero, zero' => [0, 0],
			'zero, value' => [0, 400],
			'value, zero' => [400, 0],
			'negative' => [-1, 400],
			'not numeric' => ['abc', 400],
		];
	}

	public function test_gcd_valid_values_are_unchanged() {
		$this->assertEquals(2000, $this->callProtected('gcd', 6000, 4000));
		$this->assertEquals(1, $this->callProtected('gcd', 1920, 1081));
		$this->assertEquals(400, $this->callProtected('gcd', 400, 400));
	}

	public function test_my_gcd_does_not_fail_on_zero() {
		$this->assertSame(0, $this->callProtected('my_gcd', 0, 0));
		$this->assertSame(0, $this->callProtected('my_gcd', 5, 0));
		$this->assertEquals(2000, $this->callProtected('my_gcd', 6000, 4000));
	}

	public function test_missing_original_image_throws_defined_exception() {
		\WP_Mock::userFunction('absint', ['return' => function($v) { return abs((int)$v); } ]);
		\WP_Mock::userFunction('wp_get_attachment_image_src', ['return' => false]);
		\WP_Mock::userFunction('wp_get_original_image_path', ['return' => '/does/not/exist/original.jpg']);

		$this->expectException(\crop_thumbnails\CPT_ImageFileMissingException::class);
		$this->callProtected('getUncroppedImageData', 7, 'original_image');
	}

	public function test_image_size_without_dimensions_throws_defined_exception() {
		\WP_Mock::userFunction('absint', ['return' => function($v) { return abs((int)$v); } ]);
		\WP_Mock::userFunction('wp_get_attachment_image_src', ['return' => false]);

		$this->expectException(\crop_thumbnails\CPT_ImageFileMissingException::class);
		$this->callProtected('getUncroppedImageData', 7, 'full');
	}

	public function test_uncropped_image_data_with_valid_image() {
		\WP_Mock::userFunction('wp_get_attachment_image_src', ['return' => ['http://example.org/a.jpg', 6000, 4000, false]]);

		$result = $this->callProtected('getUncroppedImageData', 7, 'full');
		$this->assertSame('3:2', $result['printRatio']);
		$this->assertEquals(2000, $result['gcd']);
		$this->assertEquals(6000, $result['width']);
	}

	/**
	 * Check if the forbidden Exception gets fired
	 *
	public function test_exception_invalid_argument() {
		//TODO
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn([]);
		self::$testObj->shouldReceive('fixJsLangStrings')->andReturn('');


		$exceptionFired = false;
		try {
			$result = self::$testObj->getCropData();
		} catch(InvalidArgumentException $e) {
			$exceptionFired = true;
		}
		$this->assertTrue($exceptionFired,'No exception fired');
	}
	//*/
}