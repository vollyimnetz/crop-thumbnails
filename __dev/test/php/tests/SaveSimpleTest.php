<?php
namespace crop_thumbnails\tests;
include_once __DIR__.'/CptTestCase.php';

class SaveSimpleTest extends CptTestCase {

	public function setUp():void {
		\WP_Mock::setUp();

		//needed by cptSettings
		\WP_Mock::userFunction( 'is_admin', )->andReturn( true );

		\WP_Mock::userFunction( '__', ['return' => function($param) { return $param; } ]);

		\WP_Mock::userFunction( 'current_user_can',[ 'return' => true ]);
	}

	public function tearDown():void {
		\WP_Mock::tearDown();
	}


	private static function doDefaultMocks() {
		\WP_Mock::userFunction( 'check_ajax_referer',[
			'return' => true
		]);
		\WP_Mock::userFunction( 'wp_basename',[
			'return' => function($path,$suffix) {
				//this is the content of the real function @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/formatting.php#L0
				return urldecode( basename( str_replace( [ '%2F', '%5C' ], '/', urlencode( $path ) ), $suffix ) );
			}
		]);
		\WP_Mock::userFunction( 'trailingslashit',[
			'return' => function($string) {
				//this is the content of the real function @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/formatting.php#L0
				return rtrim( $string, '/\\' ) . '/';
			}
		]);
		\WP_Mock::userFunction( 'wp_crop_image');

		/*
		\WP_Mock::userFunction( 'get_option',[
			'return' => function($string) {
				return [];
			}
		]);
		\WP_Mock::userFunction( 'get_intermediate_image_sizes',[
			'return' => function($string) {
				return [];
			}
		]);*/
	}


	/** @test **/
	public function should_fail_if_called_without_any_request_data() {
		/** SETUP **/
		self::doDefaultMocks();
		$cpt = $this->get_CptSaveThumbnail();

		/** TEST **/
		$request = \Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_body')->andReturn('');
		$result = $cpt->saveThumbnail($request);

		/** CHECK **/
		$this->assertFalse(isset($result['debug']), 'debug data returned');
		$this->assertTrue(!empty($result['error']), 'No error message returned');
		$this->assertEquals('ERROR: Submitted data is incomplete.', $result['error'], 'Wrong error message returned');
	}

	/** @test **/
	public function should_fail_if_image_could_not_be_found() {
		/** SETUP **/
		self::doDefaultMocks();
		$cpt = $this->get_CptSaveThumbnail();

		\WP_Mock::userFunction('get_post')->andReturn(null);//image can not be found

		/** TEST **/
		$request = \Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_body')->andReturn(self::getSimpleTestData());
		$result = $cpt->saveThumbnail($request);

		/** CHECK **/
		$this->assertTrue(!empty($result['error']));
		$this->assertEquals('ERROR: Can\'t find original image in database!', $result['error']);
	}


	/** @test **/
	public function success_only_validation() {
		/** SETUP **/
		self::doDefaultMocks();
		$cpt = $this->get_CptSaveThumbnail();

		\WP_Mock::userFunction('get_post', [
			'return' => new \stdClass()
		]);
		\WP_Mock::userFunction( 'get_attached_file',[ 'return' => true ]);
		\WP_Mock::userFunction( 'wp_get_attachment_metadata',[ 'return' => true ]);
		\WP_Mock::userFunction( 'wp_update_attachment_metadata',[ 'return' => true ]);

		self::$settingsMock->shouldReceive('getImageSizes')->andReturn([]);

		/** TEST **/
		$request = \Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_body')->andReturn(self::getSimpleTestData());
		$result = $cpt->saveThumbnail($request);

		/** CHECK **/
		$this->assertTrue(!empty($result['success']));
	}


	private static function getSimpleTestData() {
		return '{
		"crop_thumbnails":
			{
				"selection":{
					"x":0,
					"y":535.3459119496855,
					"x2":664.6540880503145,
					"y2":1200,
					"w":664.6540880503145,
					"h":664.6540880503145,
					"cropBaseSize": "original_image"
				},
				"sourceImageId":169,
				"activeImageSizes":[
					{
						"name":"thumbnail",
						"width":150,
						"height":150,
						"ratio":1,
						"crop":true
					}
				]
			}
		}';
	}
}
