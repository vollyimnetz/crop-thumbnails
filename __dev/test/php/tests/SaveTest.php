<?php
include_once __DIR__.'/CptTestCase.php';

class SaveTest extends CptTestCase {
	
	static $cpt;
	
	public function setUp() {
		\WP_Mock::setUp();
		
		//needed by cptSettings
		\WP_Mock::wpFunction( 'is_admin',[ 'return' => true ]);

		\WP_Mock::wpFunction( 'current_user_can',[ 'return' => true ]);
		
		\WP_Mock::wpFunction( 'check_ajax_referer',[ 'return' => true ]);
		
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
		
		\WP_Mock::wpFunction( '__',[
			'return' => function($param) { return $param; } 
		]);
		
		\WP_Mock::wpFunction('get_post', [ 
			'return' => new stdClass()
		]);

		\WP_Mock::wpFunction( 'wp_check_filetype',[ 
			'return' => function($param) { return ['ext'=>'jpg', 'type'=>'image/jpeg']; } 
		]);
		
		self::$cpt = $this->get_CptSaveThumbnail();
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
	 * - 1 image size ('new-image-size') has been added after the attachement was uploaded (so the metadata has to be updated)
	 */
	public function success_simple() {
		/** SETUP **/
		$that = $this;
		
		$testData = self::getSimpleTestData();
		$_REQUEST['crop_thumbnails'] = $testData;
		$testData = json_decode($testData);
		
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn(self::test_getImageSizes());
		
		$INPUT_get_attached_file = [];
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => function($id) use (&$INPUT_get_attached_file) {
				$INPUT_get_attached_file = [$id];
				return __DIR__.'/data/test.jpg';
			},
			'times' => 1
		]);
		
		
		$attachementMetadata = $this->test_get_attachement_metadata();
		$INPUT_wp_get_attachment_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => function($id,$bool) use ($that,$testData,$attachementMetadata,&$INPUT_wp_get_attachment_metadata) {
				$INPUT_wp_get_attachment_metadata = [$id,$bool];
				return $attachementMetadata;
			},
			'times' => 1
		]);
		
		$INPUT_wp_update_attachement_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => function($imageId,$metadata) use (&$INPUT_wp_update_attachement_metadata) {
				$INPUT_wp_update_attachement_metadata = [$imageId,$metadata];
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
				
				//TODO add more asserts
				
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
		#print_r($result);
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
		$this->assertTrue(!empty($result->success),'The result should not return an not empty success message.');
		
		//changedImageName should have an value with "new-image-size"
		$sizeName = 'new-image-size';
		$this->assertEquals($result->changedImageName->$sizeName, 'new/path/new-image-size-600x600.jpg');
		
		//check $INPUT_wp_update_attachement_metadata
		//the metadata should have an additional entry "new-image-size"
		$newAttachementMetadata = $attachementMetadata;
		$newAttachementMetadata['sizes']['new-image-size'] = [
			'file' => 'test-600x600.jpg',
			'width' => 600,
			'height' => 600,
			'mime-type' => 'image/jpeg',
		];

		//the metadata have an added cpt_last_cropping_data
		$newAttachementMetadata['sizes']['thumbnail']['cpt_last_cropping_data'] = [
			'x' => 0,
			'y' => 535,
			'x2' => 664,
			'y2' => 1200,
			'original_width' => 3000,
			'original_height' => 2000,
		];
		$newAttachementMetadata['sizes']['strange-image-ratio']['cpt_last_cropping_data'] = [
			'x' => 0,
			'y' => 535,
			'x2' => 664,
			'y2' => 1200,
			'original_width' => 3000,
			'original_height' => 2000,
		];
		$newAttachementMetadata['sizes']['normal1x1']['cpt_last_cropping_data'] = [
			'x' => 0,
			'y' => 535,
			'x2' => 664,
			'y2' => 1200,
			'original_width' => 3000,
			'original_height' => 2000,
		];
		$newAttachementMetadata['sizes']['new-image-size']['cpt_last_cropping_data'] = [
			'x' => 0,
			'y' => 535,
			'x2' => 664,
			'y2' => 1200,
			'original_width' => 3000,
			'original_height' => 2000,
		];

		$this->assertEquals($INPUT_wp_update_attachement_metadata[0],$testData->sourceImageId);
		$this->assertArrayEquals($INPUT_wp_update_attachement_metadata[1],$newAttachementMetadata);
		
		
		//check $INPUT_wp_get_attachment_metadata
		$that->assertEquals($INPUT_wp_get_attachment_metadata[0], $testData->sourceImageId);
		$that->assertTrue($INPUT_wp_get_attachment_metadata[1], true);
		
		//check $INPUT_get_attached_file
		$that->assertEquals($INPUT_get_attached_file[0],$testData->sourceImageId);
	}
	
	/** @test **/
	public function success_with_dynamic_width() {
		/** SETUP **/
		$that = $this;
		
		$testData = self::getDynamic2TestData();
		$_REQUEST['crop_thumbnails'] = $testData;
		$testData = json_decode($testData);
		
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn(self::test_getImageSizes());
		
		$INPUT_get_attached_file = [];
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => function($id) use (&$INPUT_get_attached_file) {
				$INPUT_get_attached_file = [$id];
				return __DIR__.'/data/test.jpg';
			},
			'times' => 1
		]);
		
		$attachementMetadata = $this->test_get_attachement_metadata();
		$INPUT_wp_get_attachment_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => function($id,$bool) use ($that,$testData,$attachementMetadata,&$INPUT_wp_get_attachment_metadata) {
				$INPUT_wp_get_attachment_metadata = [$id,$bool];
				return $attachementMetadata;
			},
			'times' => 1
		]);
		
		$INPUT_wp_update_attachement_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => function($imageId,$metadata) use (&$INPUT_wp_update_attachement_metadata) {
				$INPUT_wp_update_attachement_metadata = [$imageId,$metadata];
				return true;
			},
			'times' => 1
		]);
		
		$INPUT_wp_get_attachment_image_src = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_image_src',[
			'return' => function($imageId,$imageSizeName) use (&$INPUT_wp_get_attachment_image_src) {
				$INPUT_wp_get_attachment_image_src = [$imageId,$imageSizeName];
				return array('new/path/new-image-size-3000x500.jpg',3000,500);
			},
			'times' => 1
		]);
	
		
		$dummyBaseFile = __DIR__.'/data/dummy.jpg';
		$tmpFile = __DIR__.'/data/test-check.jpg';
		$INPUT_wp_cron_image = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_crop_image',[
			'return' => function($imageId,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file) use ($dummyBaseFile,$tmpFile,&$INPUT_wp_cron_image) {
				//prepare a file, so the function can copy it to the new location
				copy($dummyBaseFile, $tmpFile);
				$INPUT_wp_cron_image = [$imageId,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file];
				return $tmpFile;
			},
			'times' => 1
		]);
		
		self::$settingsMock->shouldReceive('getUploadDir')->andReturn(__DIR__.DIRECTORY_SEPARATOR.'data');
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(!empty($result),'Invalid JSON returned');
		#print_r($result);
		$this->assertTrue(!file_exists($tmpFile),'Temporary file where not deleted');
		
		$file1 = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'test-3000x500.jpg';
		
		//did the function copy the image file correctly?
		$this->assertTrue(file_exists($file1),'New Image (3000x500) was not created.');
		
		//did the function uses the correct file (the file that was returned by the wp_crop-function)
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file1),'The wrong file was coppied.');
		
		$this->assertEquals($INPUT_wp_get_attachment_image_src[0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_get_attachment_image_src[1], 'dynamic-2');
		
		$this->assertEquals($INPUT_wp_cron_image[0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_cron_image[1], 1009);
		$this->assertEquals($INPUT_wp_cron_image[2], 364);
		$this->assertEquals($INPUT_wp_cron_image[3], 1312);
		$this->assertEquals($INPUT_wp_cron_image[4], 219);
		$this->assertEquals($INPUT_wp_cron_image[5], 3000);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[6], 500);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[7], false);
		$this->assertEquals($INPUT_wp_cron_image[8], $file1);
		
		/** CLEANUP **/
		@unlink($file1);
		
		//did the function return correct values
		$this->assertTrue(isset($result->debug),'The result should return debug values.');
		$this->assertTrue(empty($result->error),'The result should not return any errors.');
		$this->assertTrue(empty($result->processingErrors),'The result should not return any processingErrors.');
		$this->assertTrue(!empty($result->success),'The result should not return an not empty success message.');
		
		//changedImageName should have an value with "new-image-size"
		$sizeName = 'dynamic-2';
		$this->assertEquals($result->changedImageName->$sizeName, 'new/path/new-image-size-3000x500.jpg');
		
		//check $INPUT_wp_get_attachment_metadata
		$that->assertEquals($INPUT_wp_get_attachment_metadata[0], $testData->sourceImageId);
		$that->assertTrue($INPUT_wp_get_attachment_metadata[1], true);
		
		//check $INPUT_wp_update_attachement_metadata
		//the metadata should have an changed value for dynamic-2
		$newAttachementMetadata = $attachementMetadata;
		$newAttachementMetadata['sizes']['dynamic-2']['cpt_last_cropping_data'] = [
			'x' => 1009,
			'y' => 364,
			'x2' => 2321,
			'y2' => 583,
			'original_width' => 3000,
			'original_height' => 2000,
		];
		$this->assertEquals($INPUT_wp_update_attachement_metadata[0],$testData->sourceImageId);
		$this->assertArrayEquals($INPUT_wp_update_attachement_metadata[1],$newAttachementMetadata);
		
		//check $INPUT_get_attached_file
		$that->assertEquals($INPUT_get_attached_file[0],$testData->sourceImageId);
	}
	
	/** @test **/
	public function success_with_dynamic_height() {
		/** SETUP **/
		$that = $this;
		
		$testData = self::getDynamic1TestData();
		$_REQUEST['crop_thumbnails'] = $testData;
		$testData = json_decode($testData);
		
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn(self::test_getImageSizes());
		
		$INPUT_get_attached_file = [];
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => function($id) use (&$INPUT_get_attached_file) {
				$INPUT_get_attached_file = [$id];
				return __DIR__.'/data/test.jpg';
			},
			'times' => 1
		]);
		
		$attachementMetadata = $this->test_get_attachement_metadata();
		$INPUT_wp_get_attachment_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => function($id,$bool) use ($that,$testData,$attachementMetadata,&$INPUT_wp_get_attachment_metadata) {
				$INPUT_wp_get_attachment_metadata = [$id,$bool];
				return $attachementMetadata;
			},
			'times' => 1
		]);
		
		$INPUT_wp_update_attachement_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => function($imageId,$metadata) use (&$INPUT_wp_update_attachement_metadata) {
				$INPUT_wp_update_attachement_metadata = [$imageId,$metadata];
				return true;
			},
			'times' => 1
		]);
		
		$INPUT_wp_get_attachment_image_src = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_image_src',[
			'return' => function($imageId,$imageSizeName) use (&$INPUT_wp_get_attachment_image_src) {
				$INPUT_wp_get_attachment_image_src = [$imageId,$imageSizeName];
				return array('new/path/new-image-size-500x2000.jpg',500,2000);
			},
			'times' => 1
		]);
	
		
		$dummyBaseFile = __DIR__.'/data/dummy.jpg';
		$tmpFile = __DIR__.'/data/test-check.jpg';
		$INPUT_wp_cron_image = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_crop_image',[
			'return' => function($imageId,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file) use ($dummyBaseFile,$tmpFile,&$INPUT_wp_cron_image) {
				//prepare a file, so the function can copy it to the new location
				copy($dummyBaseFile, $tmpFile);
				$INPUT_wp_cron_image = [$imageId,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file];
				return $tmpFile;
			},
			'times' => 1
		]);
		
		self::$settingsMock->shouldReceive('getUploadDir')->andReturn(__DIR__.DIRECTORY_SEPARATOR.'data');
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(!empty($result),'Invalid JSON returned');
		#print_r($result);
		$this->assertTrue(!file_exists($tmpFile),'Temporary file where not deleted');
		
		$file1 = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'test-500x2000.jpg';
		
		//did the function copy the image file correctly?
		$this->assertTrue(file_exists($file1),'New Image (500x2000) was not created.');
		
		//did the function uses the correct file (the file that was returned by the wp_crop-function)
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file1),'The wrong file was coppied.');
		
		$this->assertEquals($INPUT_wp_get_attachment_image_src[0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_get_attachment_image_src[1], 'dynamic-1');
		
		$this->assertEquals($INPUT_wp_cron_image[0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_cron_image[1], 1583);
		$this->assertEquals($INPUT_wp_cron_image[2], 345);
		$this->assertEquals($INPUT_wp_cron_image[3], 236);
		$this->assertEquals($INPUT_wp_cron_image[4], 944);
		$this->assertEquals($INPUT_wp_cron_image[5], 500);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[6], 2000);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[7], false);
		$this->assertEquals($INPUT_wp_cron_image[8], $file1);
		
		/** CLEANUP **/
		@unlink($file1);
		
		//did the function return correct values
		$this->assertTrue(isset($result->debug),'The result should return debug values.');
		$this->assertTrue(empty($result->error),'The result should not return any errors.');
		$this->assertTrue(empty($result->processingErrors),'The result should not return any processingErrors.');
		$this->assertTrue(!empty($result->success),'The result should not return an not empty success message.');
		
		//changedImageName should have an value with "new-image-size"
		$sizeName = 'dynamic-1';
		$this->assertEquals($result->changedImageName->$sizeName, 'new/path/new-image-size-500x2000.jpg');
		
		//check $INPUT_wp_get_attachment_metadata
		$that->assertEquals($INPUT_wp_get_attachment_metadata[0], $testData->sourceImageId);
		$that->assertTrue($INPUT_wp_get_attachment_metadata[1], true);
		
		//check $INPUT_wp_update_attachement_metadata
		//the metadata should have an additional entry "new-image-size"
		$newAttachementMetadata = $attachementMetadata;
		$newAttachementMetadata['sizes']['dynamic-1']['cpt_last_cropping_data'] = [
			'x' => 1583,
			'y' => 345,
			'x2' => 1819,
			'y2' => 1289,
			'original_width' => 3000,
			'original_height' => 2000,
		];
		$this->assertEquals($INPUT_wp_update_attachement_metadata[0],$testData->sourceImageId);
		$this->assertArrayEquals($INPUT_wp_update_attachement_metadata[1],$newAttachementMetadata);
		
		//check $INPUT_get_attached_file
		$that->assertEquals($INPUT_get_attached_file[0],$testData->sourceImageId);
	}
	
	
	/** @test **/
	public function success_with_dynamic_zero() {
		/** SETUP **/
		$that = $this;
		
		$testData = self::getDynamicZero();
		$_REQUEST['crop_thumbnails'] = $testData;
		$testData = json_decode($testData);
		
		self::$settingsMock->shouldReceive('getImageSizes')->andReturn(self::test_getImageSizes());
		
		$INPUT_get_attached_file = [];
		\WP_Mock::wpFunction( 'get_attached_file',[
			'return' => function($id) use (&$INPUT_get_attached_file) {
				$INPUT_get_attached_file = [$id];
				return __DIR__.'/data/test.jpg';
			},
			'times' => 1
		]);
		
		$attachementMetadata = $this->test_get_attachement_metadata();
		$INPUT_wp_get_attachment_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_metadata',[
			'return' => function($id,$bool) use ($that,$testData,$attachementMetadata,&$INPUT_wp_get_attachment_metadata) {
				$INPUT_wp_get_attachment_metadata = [$id,$bool];
				return $attachementMetadata;
			},
			'times' => 1
		]);
		
		$INPUT_wp_update_attachement_metadata = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_update_attachment_metadata',[
			'return' => function($imageId,$metadata) use (&$INPUT_wp_update_attachement_metadata) {
				$INPUT_wp_update_attachement_metadata = [$imageId,$metadata];
				return true;
			},
			'times' => 1
		]);
		
		$INPUT_wp_get_attachment_image_src = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_get_attachment_image_src',[
			'return' => function($imageId,$imageSizeName) use (&$INPUT_wp_get_attachment_image_src) {
				$INPUT_wp_get_attachment_image_src[] = [$imageId,$imageSizeName];
				return array('new/path/new-image-size.jpg',123,123);
			},
			'times' => 2
		]);
	
		
		$dummyBaseFile = __DIR__.'/data/dummy.jpg';
		$tmpFile = __DIR__.'/data/test-check.jpg';
		$INPUT_wp_cron_image = [];//this will be filled once the mock has run
		\WP_Mock::wpFunction( 'wp_crop_image',[
			'return' => function($imageId,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file) use ($dummyBaseFile,$tmpFile,&$INPUT_wp_cron_image) {
				//prepare a file, so the function can copy it to the new location
				copy($dummyBaseFile, $tmpFile);
				$INPUT_wp_cron_image[] = [$imageId,$src_x,$src_y,$src_w,$src_h,$dst_w,$dst_h,$src_abs,$dst_file];
				return $tmpFile;
			},
			'times' => 2
		]);
		
		self::$settingsMock->shouldReceive('getUploadDir')->andReturn(__DIR__.DIRECTORY_SEPARATOR.'data');
		
		/** TEST **/
		ob_start();
		self::$cpt->saveThumbnail();
		$result = json_decode(ob_get_clean());
		
		/** CHECK **/
		$this->assertTrue(!empty($result),'Invalid JSON returned');
		#print_r($result);
		$this->assertTrue(!file_exists($tmpFile),'Temporary file where not deleted');
		
		
		$file1 = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'test-750x500.jpg';
		$file2 = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'test-400x266.jpg';
		
		//did the function copy the image file correctly?
		$this->assertTrue(file_exists($file1),'New Image was not created (750x500).');
		$this->assertTrue(file_exists($file2),'New Image was not created (400x266).');
		
		//did the function uses the correct file (the file that was returned by the wp_crop-function)
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file1),'The wrong file was coppied (750x500).');
		$this->assertEquals(md5_file($dummyBaseFile),md5_file($file2),'The wrong file was coppied (400x266).');
		
		
		$this->assertEquals($INPUT_wp_get_attachment_image_src[0][0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_get_attachment_image_src[0][1], 'dynamic-zero-width');
		$this->assertEquals($INPUT_wp_get_attachment_image_src[1][0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_get_attachment_image_src[1][1], 'dynamic-zero-height');
		
		$this->assertEquals($INPUT_wp_cron_image[0][0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_cron_image[0][1], 1654);
		$this->assertEquals($INPUT_wp_cron_image[0][2], 874);
		$this->assertEquals($INPUT_wp_cron_image[0][3], 924);
		$this->assertEquals($INPUT_wp_cron_image[0][4], 616);
		$this->assertEquals($INPUT_wp_cron_image[0][5], 750);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[0][6], 500);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[0][7], false);
		$this->assertEquals($INPUT_wp_cron_image[0][8], $file1);
		
		$this->assertEquals($INPUT_wp_cron_image[1][0], $testData->sourceImageId);
		$this->assertEquals($INPUT_wp_cron_image[1][1], 1654);
		$this->assertEquals($INPUT_wp_cron_image[1][2], 874);
		$this->assertEquals($INPUT_wp_cron_image[1][3], 924);
		$this->assertEquals($INPUT_wp_cron_image[1][4], 616);
		$this->assertEquals($INPUT_wp_cron_image[1][5], 400);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[1][6], 266);//this has to be equals to the size in add_image_size
		$this->assertEquals($INPUT_wp_cron_image[1][7], false);
		$this->assertEquals($INPUT_wp_cron_image[1][8], $file2);
		
		/** CLEANUP **/
		@unlink($file1);
		@unlink($file2);
		
		//did the function return correct values
		$this->assertTrue(isset($result->debug),'The result should return debug values.');
		$this->assertTrue(empty($result->error),'The result should not return any errors.');
		$this->assertTrue(empty($result->processingErrors),'The result should not return any processingErrors.');
		$this->assertTrue(!empty($result->success),'The result should not return an not empty success message.');
		
		//changedImageName should have an value with "new-image-size"
		$sizeName = 'dynamic-zero-width';
		$this->assertEquals($result->changedImageName->$sizeName, 'new/path/new-image-size.jpg');
		$sizeName = 'dynamic-zero-height';
		$this->assertEquals($result->changedImageName->$sizeName, 'new/path/new-image-size.jpg');
		
		//check $INPUT_wp_get_attachment_metadata
		$that->assertEquals($INPUT_wp_get_attachment_metadata[0], $testData->sourceImageId);
		$that->assertTrue($INPUT_wp_get_attachment_metadata[1], true);
		
		//check $INPUT_wp_update_attachement_metadata
		//the metadata should have an additional entry "new-image-size"
		$newAttachementMetadata = $attachementMetadata;
		$newAttachementMetadata['sizes']['dynamic-zero-height']['cpt_last_cropping_data'] = [
			'x' => 1654,
			'y' => 874,
			'x2' => 2578,
			'y2' => 1490,
			'original_width' => 3000,
			'original_height' => 2000,
		];
		$newAttachementMetadata['sizes']['dynamic-zero-width']['cpt_last_cropping_data'] = $newAttachementMetadata['sizes']['dynamic-zero-height']['cpt_last_cropping_data'];
		
		$this->assertEquals($INPUT_wp_update_attachement_metadata[0],$testData->sourceImageId);
		$this->assertArrayEquals($INPUT_wp_update_attachement_metadata[1],$newAttachementMetadata);
		
		//check $INPUT_get_attached_file
		$that->assertEquals($INPUT_get_attached_file[0],$testData->sourceImageId);
	}
	
	
	private static function getDynamic1TestData() {
		return '{
			"selection":{
				"x":1583.3333333333333,
				"y":345.9119496855346,
				"x2":1819.1823899371068,
				"y2":1289.308176100629,
				"w":235.8490566037736,
				"h":943.3962264150944
			},
			"sourceImageId":169,
			"activeImageSizes":[
				{
					"name":"dynamic-1",
					"width":500,
					"height":9999,
					"ratio":0.25,
					"crop":true
				}
			]
		}';
	}
	
	private static function getDynamic2TestData() {
		return '{
			"selection":{
				"x":1009.1743119266055,
				"y":364.6788990825688,
				"x2":2321.1009174311926,
				"y2":583.3333333333334,
				"w":1311.926605504587,
				"h":218.65443425076452
			},
			"sourceImageId":169,
			"activeImageSizes":[
				{
					"name":"dynamic-2",
					"width":9999,
					"height":500,
					"ratio":6,
					"crop":true
				}
			]
		}';
	}
	
	private static function getDynamicZero() {
		return '{
			"selection":{
				"x":1654.0880503144654,
				"y":874.2138364779875,
				"x2":2578.6163522012575,
				"y2":1490.566037735849,
				"w":924.5283018867924,
				"h":616.3522012578617
			},
			"sourceImageId":169,
			"activeImageSizes":[
				{
					"name":"dynamic-zero-width",
					"width":0,
					"height":500,
					"ratio":1.5,
					"crop":true
				},
				{
					"name":"dynamic-zero-height",
					"width":400,
					"height":0,
					"ratio":1.5,
					"crop":true
				}
			]
		}';
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
	
	
	public static function test_getImageSizes() {
		return [
			'thumbnail' => [
				'width' => 150,
				'height' => 150,
				'crop' => 1,
				'name' => 'thumbnail',
				'id' => 'thumbnail'
			],
			'medium' => [
				'width' => 300,
				'height' => 300,
				'crop' => 0,
				'name' => 'medium',
				'id' => 'medium'
			],
			'large' => [
				'width' => 1024,
				'height' => 1024,
				'crop' => 0,
				'name' => 'large',
				'id' => 'large'
			],
			'post-thumbnail' => [
				'width' => 300,
				'height' => 200,
				'crop' => 1,
				'name' => 'post-thumbnail',
				'id' => 'post-thumbnail'
			],
			'dynamic-1' => [//dynamic image size with a width of 500 - height is the height of the attachement-image
				'width' => 500,
				'height' => 9999,
				'crop' => 1,
				'name' => 'dynamic-1',
				'id' => 'dynamic-1'
			],
			'dynamic-2' => [//dynamic image size with a height of 500 - width is the width of the attachement-image
				'width' => 9999,
				'height' => 500,
				'crop' => 1,
				'name' => 'dynamic-2',
				'id' => 'dynamic-2'
			],
			'dynamic-zero-width' => [//a dynamic image size wich should be not enabled for crop (cause width=0 indicates no crop)
				'width' => 0,
				'height' => 500,
				'crop' => 1,
				'name' => 'dynamic-zero-width',
				'id' => 'dynamic-zero-width'
			],
			'dynamic-zero-height' => [//a dynamic image size wich should be not enabled for crop (cause width=0 indicates no crop)
				'width' => 400,
				'height' => 0,
				'crop' => 1,
				'name' => 'dynamic-zero-height',
				'id' => 'dynamic-zero-height'
			],
			'strange-image-ratio' => [//a image-size with a nearly image-ratio of 1 (could be set to 1 via add_action)
				'width' => 500,
				'height' => 499,
				'crop' => 1,
				'name' => 'strange-image-ratio',
				'id' => 'strange-image-ratio'
			],
			'normal1x1' => [//a image-size with a custom name
				'width' => 500,
				'height' => 500,
				'crop' => 1,
				'name' => 'my square image-size',
				'id' => 'normal1x1'
			],
			'new-image-size' => [//this image size was added by an developer after the image was uploaded (this size will not be in the attachements metadata)
				'width' => 600,
				'height' => 600,
				'crop' => 1,
				'name' => 'new-image-size',
				'id' => 'new-image-size'
			]
		];
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
					'test' => 'some additional data, that should be unchanged'
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
				'dynamic-zero-width' => [
					'file' => 'test-750x500.jpg',
					'width' => 750,
					'height' => 500,
					'mime-type' => 'image/jpeg',
				],
				'dynamic-zero-height' => [
					'file' => 'test-400x266.jpg',
					'width' => 400,
					'height' => 266,
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
