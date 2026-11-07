<?php
namespace crop_thumbnails\tests;
include_once __DIR__.'/CptTestCase.php';

/**
 * The user needs the global plugin permission AND the permission to edit the specific attachment.
 * (an author must not be able to read or re-crop the images of other users)
 */
class SecurityTest extends CptTestCase {

	private $caps = [];

	public function setUp():void {
		\WP_Mock::setUp();
		$this->caps = ['upload_files' => true, 'edit_files' => false, 'edit_post' => true];

		\WP_Mock::userFunction('current_user_can', ['return' => function($cap, ...$args) {
			return !empty($this->caps[$cap]);
		}]);
		\WP_Mock::userFunction('absint', ['return' => function($v) { return abs((int) $v); }]);
		\WP_Mock::userFunction('__', ['return' => function($p) { return $p; }]);
		\WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
		\WP_Mock::userFunction('wp_basename', ['return' => function($path, $suffix = '') {
			return urldecode(basename(str_replace(['%2F', '%5C'], '/', urlencode($path)), $suffix));
		}]);
	}

	public function tearDown():void {
		\WP_Mock::tearDown();
	}

	/** @test **/
	public function permitted_if_global_and_object_permission_exist() {
		$this->get_CptSaveThumbnail(['debug_data' => false]);
		$this->assertTrue(\crop_thumbnails\CptSaveThumbnail::isUserPermitted(5));
	}

	/** @test **/
	public function not_permitted_if_user_may_not_edit_the_attachment() {
		$this->caps['edit_post'] = false;//i.e. an author, and the attachment belongs to another user
		$this->get_CptSaveThumbnail(['debug_data' => false]);
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isUserPermitted(5));
	}

	/** @test **/
	public function not_permitted_without_global_permission() {
		$this->caps['upload_files'] = false;
		$this->get_CptSaveThumbnail(['debug_data' => false]);
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isUserPermitted(5));
	}

	/** @test **/
	public function not_permitted_without_a_valid_image_id() {
		$this->get_CptSaveThumbnail(['debug_data' => false]);
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isUserPermitted(0));
		$this->assertFalse(\crop_thumbnails\CptSaveThumbnail::isUserPermitted(null));
	}

	private function saveRequest($imageId) {
		$request = \Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_body')->andReturn(json_encode(['crop_thumbnails' => [
			'selection' => ['x' => 0, 'y' => 0, 'x2' => 300, 'y2' => 200],
			'sourceImageId' => $imageId,
			'activeImageSizes' => [['name' => 'thumbnail']]
		]]));
		return $request;
	}

	/** @test **/
	public function saving_fails_and_nothing_is_cropped_for_a_foreign_attachment() {
		$this->caps['edit_post'] = false;
		$cpt = $this->get_CptSaveThumbnail(['debug_data' => false]);
		\WP_Mock::userFunction('get_post', ['return' => (object) ['post_type' => 'attachment']]);
		\WP_Mock::userFunction('wp_crop_image', ['times' => 0]);
		\WP_Mock::userFunction('wp_update_attachment_metadata', ['times' => 0]);

		$result = $cpt->saveThumbnail($this->saveRequest(5));

		$this->assertEquals('You are not permitted to crop the thumbnails.', $result['error']);
		$this->assertArrayNotHasKey('success', $result);
	}

	/** @test **/
	public function saving_fails_if_the_id_is_not_an_attachment() {
		$cpt = $this->get_CptSaveThumbnail(['debug_data' => false]);
		\WP_Mock::userFunction('get_post', ['return' => (object) ['post_type' => 'post']]);
		\WP_Mock::userFunction('wp_crop_image', ['times' => 0]);

		$result = $cpt->saveThumbnail($this->saveRequest(5));

		$this->assertEquals("ERROR: Can't find original image in database!", $result['error']);
	}
}
