<?php
include_once __DIR__.'/CptTestCase.php';

class EditorTest extends CptTestCase {
	
	static $testObj;
	
	public function setUp() {
		\WP_Mock::setUp();
		
		\WP_Mock::wpFunction( '__',['return' => function($param) { return $param; } ]);

		self::$testObj = $this->get_CropPostThumbnailsEditor();
		
	}

	public function tearDown() {
		self::$testObj = null;
		\WP_Mock::tearDown();
	}


	/**
	 * @test 
	 * Check if the forbidden Exception gets fired
	 */
	public function exception_invalid_argument() {
		/*
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
		*/
	}
}