<?php
include_once __DIR__.'/CptTestCase.php';

class SaveSimpleTest extends CptTestCase {
	
	public function setUp() {
		\WP_Mock::setUp();
		
		//needed by cptSettings
		\WP_Mock::wpFunction( 'is_admin',[ 'return' => true ]);
		
		\WP_Mock::wpFunction( '__',['return' => function($param) { return $param; } ]);

		\WP_Mock::wpFunction( 'current_user_can',[ 'return' => true ]);
	}

	public function tearDown() {
		\WP_Mock::tearDown();
	}
	
	
	private static function doDefaultMocks() {
		\WP_Mock::wpFunction( 'check_ajax_referer',[
			'return' => true
		]);
		\WP_Mock::wpFunction( 'wp_basename',[
			'return' => function($path,$suffix) { 
				//this is the content of the real function @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/formatting.php#L0
				return urldecode( basename( str_replace( [ '%2F', '%5C' ], '/', urlencode( $path ) ), $suffix ) ); 
			}
		]);
		\WP_Mock::wpFunction( 'trailingslashit',[
			'return' => function($string) { 
				//this is the content of the real function @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/formatting.php#L0
				return rtrim( $string, '/\\' ) . '/'; 
			}
		]);/*
		\WP_Mock::wpFunction( 'get_option',[
			'return' => function($string) { 
				return [];
			}
		]);
		\WP_Mock::wpFunction( 'get_intermediate_image_sizes',[
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
		ob_start();
		$cpt->saveThumbnail();
		$result = ob_get_clean();
		$result = json_decode($result);
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(!empty($result->error));
		$this->assertEquals($result->error,'ERROR: Submitted data is incomplete.');
	}
	
	/** @test **/
	public function should_fail_if_image_could_not_be_found() {
		/** SETUP **/
		self::doDefaultMocks();
		$cpt = $this->get_CptSaveThumbnail();
		$_REQUEST['crop_thumbnails'] = self::getSimpleTestData();
		
		\WP_Mock::wpFunction('get_post',null);//image can not be found
		
		/** TEST **/
		ob_start();
		$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(!empty($result->error));
		$this->assertEquals($result->error,'ERROR: Can\'t find original image in database!');
	}
	
	
	/** @test **/
	public function success_only_validation() {
		/** SETUP **/
		self::doDefaultMocks();
		$cpt = $this->get_CptSaveThumbnail();
		$_REQUEST['crop_thumbnails'] = self::getSimpleTestData();
		
		\WP_Mock::wpFunction('get_post', [ 
			'return' => new \stdClass()
		]);
		\WP_Mock::wpFunction( 'get_attached_file',[ 'return' => true ]);
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[ 'return' => true ]);
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[ 'return' => true ]);
		
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn([]);
		
		/** TEST **/
		ob_start();
		$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(empty($result->error));
		$this->assertTrue(!empty($result->success));
	}
	
	
	/** @test **/
	public function ajax_referer_should_be_checked() {
		/** SETUP **/
		\WP_Mock::wpFunction( 'check_ajax_referer',[ 'return' => false ]);
		
		$cpt = $this->get_CptSaveThumbnail();
		
		/** TEST **/
		ob_start();
		$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(!empty($result),'Invalid JSON returned');
		$this->assertEquals($result->error,'ERROR: Security Check failed (maybe a timeout - please try again).');
	}
	
	
	private static function getSimpleTestData() {
		return '
		{
			"selection":{
				"x":0,
				"y":535.3459119496855,
				"x2":664.6540880503145,
				"y2":1200,
				"w":664.6540880503145,
				"h":664.6540880503145
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
		}';
	}
}
