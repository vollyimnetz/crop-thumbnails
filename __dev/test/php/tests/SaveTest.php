<?php
include_once 'CptTestCase.php';

class SaveTest extends TestCase {
	
	static $cpt;
	
	public function setUp() {
		\WP_Mock::setUp();
		
		//needed by cptSettings
		\WP_Mock::wpFunction( 'is_admin',[
			'return' => true
		]);
		
		\WP_Mock::wpFunction( 'check_ajax_referer',[
			'return' => true
		]);
		\WP_Mock::wpFunction( 'wp_basename',[
			'return' => function($path,$suffix) { 
				//this is the content of the real function @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/formatting.php#L0
				return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ), $suffix ) ); 
			}
		]);
		\WP_Mock::wpFunction( 'trailingslashit',[
			'return' => function($string) { 
				//this is the content of the real function @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/formatting.php#L0
				return rtrim( $string, '/\\' ) . '/'; 
			}
		]);
		
		\WP_Mock::wpFunction( '__',['return' => function($param) { return $param; } ]);
		
		
		self::$cpt = $this->getTestObject();
	}

	public function tearDown() {
		self::$cpt = null;
		\WP_Mock::tearDown();
	}
	
	
	/**
	 * @test 
	 * This test will check if a request with 4 image sizes will use the correct functions.
	 * - 2 image sizes are quite ordanary
	 * - 1 image size ('strange-image-ratio') is a little off ratio
	 * - 1 image size ('new-image-size') has been added after the attachement was uploaded (no metadata exists)
	 */
	public function success_simple() {
		/** SETUP **/
		$that = $this;
		
		$testData = self::getSimpleTestData();
		$_REQUEST['crop_thumbnails'] = $testData;
		$testData = json_decode($testData);
		
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn(self::test_getImageSizes());
		
		\WP_Mock::wpFunction('get_post', [ 
			'return' => new stdClass()
		]);
		
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => function($id) use ($that,$testData) {
				$that->assertEquals($id,$testData->sourceImageId);
				return __DIR__.'/data/test.jpg';
			},
			'times' => 1
		]);
		
		
		$attachementMetadata = $this->test_get_attachement_metadata();
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => function($id,$bool) use ($that,$testData,$attachementMetadata) {
				$that->assertEquals($id,$testData->sourceImageId);
				$that->assertTrue($bool);
				return $attachementMetadata;
			},
			'times' => 1
		]);
		
		$savedMetadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => function($imageId,$metadata) use ($that,$testData,&$savedMetadata) {
				$that->assertEquals($imageId,$testData->sourceImageId);
				$savedMetadata = $metadata;
				return true;
			},
			'times' => 1
		]);
		
		\WP_Mock::wpFunction( 'wp_get_attachment_image_src',[
			'return' => function($imageId,$imageSizeName) use ($that,$testData) {
				$that->assertEquals($imageId,$testData->sourceImageId);
				$that->assertEquals($imageSizeName,'new-image-size');
				return array('new/path/new-image-size-600x600.jpg',600,600);
			},
			'times' => 1
		]);
	
		
		$dummyBaseFile = __DIR__.'/data/dummy.jpg';
		$tmpFile = __DIR__.'/data/test-check.jpg';
		\WP_Mock::wpFunction( 'wp_crop_image',[
			'return' => function($src,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file) use ($that,$dummyBaseFile,$tmpFile) {
				//prepare a file, so the function can copy it to the new location
				copy($dummyBaseFile, $tmpFile);
				return $tmpFile;
			},
			'times' => 4
		]);
		
		
		self::$settingsMock->shouldReceive('getUploadDir')->andReturn(__DIR__.'/data');
		
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(!empty($result),'Invalid JSON returned');
		$this->assertTrue(!file_exists($tmpFile),'Temporary file where not deleted');
		
		$file1 = __DIR__.'/data/test-150x150.jpg';
		$file2 = __DIR__.'/data/test-500x499.jpg';
		$file3 = __DIR__.'/data/test-500x500.jpg';
		$file4 = __DIR__.'/data/test-600x600.jpg';
		
		//did the function copy the image file correctly?
		$this->assertTrue(file_exists($file1),'New Image (150x150) was not created.');
		$this->assertTrue(file_exists($file2),'New Image (500x499) was not created.');
		$this->assertTrue(file_exists($file3),'New Image (500x500) was not created.');
		$this->assertTrue(file_exists($file4),'New Image (600x600) was not created.');
		
		//did the function uses the correct file (the file that was returned by the wp_crop-function)
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file1),'The wrong file was coppied (150x150).');
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file2),'The wrong file was coppied (500x499).');
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file3),'The wrong file was coppied (500x500).');
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file4),'The wrong file was coppied (600x600).');
		
		/** CLEANUP **/
		@unlink($file1);
		@unlink($file2);
		@unlink($file3);
		@unlink($file4);
		
		//did the function return correct values
		$this->assertTrue(isset($result->debug),'The result should return debug values.');
		$this->assertTrue(empty($result->error),'The result should not return any errors.');
		$this->assertTrue(empty($result->processingErrors),'The result should not return any processingErrors.');
		
		$sizeName = 'new-image-size';
		$this->assertEquals($result->changedImageName->$sizeName, 'new/path/new-image-size-600x600.jpg');
		$this->assertTrue(!empty($result->success),'The result should not return an not empty success message.');
		
		//the metadata should be unchanged
		$this->assertArrayEquals($savedMetadata,$attachementMetadata);
		
	}
	
	/** test **/
	public function success_with_dynamic_width() {
		//TODO
	}
	
	/** test **/
	public function success_with_dynamic_height() {
		//TODO
	}
	
	
	public static function test_getImageSizes() {
		return [
			'thumbnail' => [
				'width' => 150,
				'height' => 150,
				'crop' => 1,
				'name' => 'thumbnail'
			],
			'medium' => [
				'width' => 300,
				'height' => 300,
				'crop' => 0,
				'name' => 'medium'
			],
			'large' => [
				'width' => 1024,
				'height' => 1024,
				'crop' => 0,
				'name' => 'large'
			],
			'post-thumbnail' => [
				'width' => 300,
				'height' => 200,
				'crop' => 1,
				'name' => 'post-thumbnail'
			],
			'dynamic-1' => [//dynamic image size with a width of 500 - height is the height of the attachement-image
				'width' => 500,
				'height' => 9999,
				'crop' => 1,
				'name' => 'dynamic-1'
			],
			'dynamic-2' => [//dynamic image size with a height of 500 - width is the width of the attachement-image
				'width' => 9999,
				'height' => 500,
				'crop' => 1,
				'name' => 'dynamic-2'
			],
			'dynamic-prevent-bug' => [//a dynamic image size wich should be not enabled for crop (cause width=0 indicates no crop)
				'width' => 0,
				'height' => 500,
				'crop' => 1,
				'name' => 'dynamic-prevent-bug'
			],
			'strange-image-ratio' => [//a image-size with a nearly image-ratio of 1 (could be set to 1 via add_action)
				'width' => 500,
				'height' => 499,
				'crop' => 1,
				'name' => 'strange-image-ratio'
			],
			'normal1x1' => [
				'width' => 500,
				'height' => 500,
				'crop' => 1,
				'name' => 'normal1x1'
			],
			'new-image-size' => [//this image size was added by an developer after the image was uploaded (this size will not be in the attachements metadata)
				'width' => 600,
				'height' => 600,
				'crop' => 1,
				'name' => 'new-image-size'
			],
			'static-1' => [
				'width' => 240,
				'height' => 120,
				'crop' => 1,
				'name' => 'Mein neuer Name'
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
				},
				{
					"name":"new-image-size",
					"width":600,
					"height":600,
					"ratio":1,
					"crop":true
				}
			]
		}';
	}
	
	public static function test_get_attachement_metadata() {
		return [
			'width' => 3000,
			'height' => 2000,
			'file' => "2016/05/test.jpg",
			'sizes' => [
				'thumbnail' => [
					'file' => 'test-150x150.jpg',
					'width' => 150,
					'height' => 150,
					'mime-type' => 'image/jpeg',
				],
				'medium' => [
					'file' => 'test-300x200.jpg',
					'width' => 300,
					'height' => 200,
					'mime-type' => 'image/jpeg',
				],
				'medium_large' => [
					'file' => 'test-768x512.jpg',
					'width' => 768,
					'height' => 512,
					'mime-type' => 'image/jpeg',
				],
				'large' => [
					'file' => 'test-1024x683.jpg',
					'width' => 1024,
					'height' => 683,
					'mime-type' => 'image/jpeg',
				],
				'post-thumbnail' => [
					'file' => 'test-300x200.jpg',
					'width' => 300,
					'height' => 200,
					'mime-type' => 'image/jpeg',
				],
				'dynamic-1' => [
					'file' => 'test-500x2000.jpg',
					'width' => 500,
					'height' => 2000,
					'mime-type' => 'image/jpeg',
				],
				'dynamic-2' => [
					'file' => 'test-3000x500.jpg',
					'width' => 3000,
					'height' => 500,
					'mime-type' => 'image/jpeg',
				],
				'dynamic-prevent-bug' => [
					'file' => 'test-750x500.jpg',
					'width' => 750,
					'height' => 500,
					'mime-type' => 'image/jpeg',
				],
				'strange-image-ratio' => [
					'file' => 'test-500x499.jpg',
					'width' => 500,
					'height' => 499,
					'mime-type' => 'image/jpeg',
				],
				'normal1x1' => [
					'file' => 'test-500x500.jpg',
					'width' => 500,
					'height' => 500,
					'mime-type' => 'image/jpeg',
				],
			],
			'image_meta' => [
				'aperture' => 0,
				'credit' => null,
				'camera' => null,
				'caption' => null,
				'created_timestamp' => 0,
				'copyright' => null,
				'focal_length' => 0,
				'iso' => 0,
				'shutter_speed' => 0,
				'title' => null,
				'orientation' => 0,
				'keywords' => []
			]
		];
	}
}
