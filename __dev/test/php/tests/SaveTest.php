<?php
include_once 'CptTestCase.php';

class SaveTest extends TestCase {
	
	static $cpt;
	
	public function setUp() {
		\WP_Mock::setUp();
		
		\WP_Mock::wpFunction( 'is_admin',[
			'return' => true
		]);
		\WP_Mock::wpFunction( 'check_ajax_referer',[
			'return' => true
		]);
		
		
		\WP_Mock::wpFunction( '__',['return' => function($param) { return $param; } ]);
		
		
		self::$cpt = $this->getTestObject();
	}

	public function tearDown() {
		self::$cpt = null;
		\WP_Mock::tearDown();
	}
	
	
	/** @test **/
	public function should_fail_if_called_without_any_request_data() {
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(!empty($result->error));
		$this->assertEquals($result->error,'ERROR: Submitted data is incomplete.');
	}
	
	/** @test **/
	public function should_fail_if_image_could_not_be_found() {
		/** SETUP **/
		
		$data = self::getSimpleTestData();
		$_REQUEST['crop_thumbnails'] = $data;
		$data = json_decode($data);
		
		\WP_Mock::wpFunction('get_post',[]);
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(!empty($result->error));
		$this->assertEquals($result->error,'ERROR: Can\'t find original image in database!');
	}
	
	
	/** @test **/
	public function success_only_validation() {
		/** SETUP **/
		
		$data = self::getSimpleTestData();
		$_REQUEST['crop_thumbnails'] = $data;
		$data = json_decode($data);
		
		\WP_Mock::wpFunction('get_post', [ 
			'return' => new stdClass()
		]);
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => true
		]);
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => true
		]);
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => true
		]);
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(empty($result->error));
		$this->assertTrue(!empty($result->success));
	}
	
	
	/** @test **/
	public function success() {
		/** SETUP **/
		$that = $this;
		
		
		$testData = self::getSimpleTestData();
		$_REQUEST['crop_thumbnails'] = $testData;
		$testData = json_decode($testData);
		
		\WP_Mock::wpFunction('get_post', [ 
			'return' => new stdClass()
		]);
		
		
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => function($id) use ($that,$testData) {
				$that->assertEquals($id,$testData->sourceImageId);
				return TEST_PLUGIN_BASE.'/images/test_image.jpg';
			},
			'times' => 1
		]);
		
		
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => function($id,$bool) use ($that,$testData) {
				$that->assertEquals($id,$testData->sourceImageId);
				$that->assertTrue($bool);
				return $this->test_get_attachement_metadata();
			}
		]);
		
		
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => true
		]);
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(isset($result->debug));
		$this->assertTrue(empty($result->error));
		$this->assertTrue(!empty($result->success));
	}
	
	public static function test_get_attachement_metadata() {
		return [
			'width' => 2400,
			'height' => 1559,
			'file' => '2011/12/press_image.jpg',
			'sizes' => [
					'thumbnail' => [
							'file' => 'press_image-150x150.jpg',
							'width' => 150,
							'height' => 150,
							'mime-type' => 'image/jpeg'
					],
					'medium' => [
							'file' => 'press_image-4-300x194.jpg',
							'width' => 300,
							'height' => 194,
							'mime-type' => 'image/jpeg'
					],
					'large' => [
							'file' => 'press_image-1024x665.jpg',
							'width' => 1024,
							'height' => 665,
							'mime-type' => 'image/jpeg'
					],
					'post-thumbnail' => [
							'file' => 'press_image-624x405.jpg',
							'width' => 624,
							'height' => 405,
							'mime-type' => 'image/jpeg'
					],
			],
			'image_meta' => [
					'aperture' => 5,
					'credit' => '',
					'camera' => 'Canon EOS-1Ds Mark III',
					'caption' => '',
					'created_timestamp' => 1323190643,
					'copyright' => '',
					'focal_length' => 35,
					'iso' => 800,
					'shutter_speed' => 0.016666666666667,
					'title' => ''
			],
		];
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
				},
				{
					"name":"strange-image-ratio",
					"width":500,
					"height":499,
					"ratio":1.002004008016,
					"crop":true
				},
				{
					"name":"normal1x1",
					"width":500,
					"height":500,
					"ratio":1,
					"crop":true
				}
			]
		}';
	}
}
