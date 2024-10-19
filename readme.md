# Crop-Thumbnails

"Crop-Thumbnails" is an Wordpress-Plugin for editing cropped image-sizes. Every wordpress-media-image that is defined via "add_image_size" could be defined as cropped image, but in the default Wordpress-Image-Editor you are not able to choose what part of the image should be shown. Thats where the plugin kicks in. "Crop-Thumbnails" provide an additional editor, which is available from all relevant positions in the backend.

## Installation

The plugin can be installed from the wordpress plugin repository.

https://wordpress.org/plugins/crop-thumbnails/

## How to add custom cropped image-sizes
This is the default wordpress way to add new image-sizes.
```php
// php
add_action('after_setup_theme', function() {
	add_image_size('my-custom-imagesize', 500, 500, true);
	//you have to set "true" to get a cropped size
	//the plugin will only handle cropped sizes
});
```

## How to add a crop thumbnail-button on a custom location
You may want to open a crop-thumbnail-modal-dialog on a custom location. To do so you can use the javascript modal-function of the plugin.

```javascript
// javascript
var modal = new CROP_THUMBNAILS_VUE.modal();
modal.open(attachementId, postType, title);
```

A full example that demonstrate adding a custom crop button right beside the default media button.

```php
// php

// perform an action on the admin footer to execute a php function
add_action('admin_footer', 'myCustomPhpFooterCode');
function myCustomPhpFooterCode() {
	//lets print some javascript code
	//in reality you may want to check on what admin-side you are, i.e. by the use of 'get_current_screen()'

	?>
	<script>
	jQuery(function($) {
		//add a button right beside the add media button - adjust if you want the button somewhere else
		$('#wp-content-media-buttons').append('<button type="button" id="myCustomButton" class="button">my custom crop button</button>');

		$('#myCustomButton').click(function() {
			/**
			 * the ID of the image you want to open
			 * you may want to read the value by javascript from somewhere
			 **/
			var attachementId = 123;

			/** the posttype decides what imagesizes should be visible - see settings **/
			var postType = 'post';

			/** the title of the modal dialog */
			var title = 'test';

			/** lets open the crop-thumbnails-modal **/
			var modal = new CROP_THUMBNAILS_VUE.modal();
			modal.open(attachementId, postType, title);
		});
	});
	</script>
	<?php
}
```

## Filters and action-hooks
The plugin provides some filters/actions if you want to adjust or extend the behaviour.

### FILTER `crop_thumbnails_before_get_validated_input`
Filter with one input-variable. Will be run directly before input validation.

### FILTER `crop_thumbnails_after_get_validated_input`
Filter with one input-variable. Will be run directly after input validation.

### FILTER `crop_thumbnails_optimize_input_before_crop`
Posibility to optimize the input directly before the crop ist done. If you use this filter the resulting value will not be saved in metadata.

### ACTION `crop_thumbnails_before_crop`
An action called directly before cropping.

### ACTION `crop_thumbnails_after_crop`
An action called directly after cropping.

### FILTER `crop_thumbnails_do_crop`
Filter for change the cropping function.

### FILTER `crop_thumbnails_should_delete_old_file`
Filter for checking if an old file should be deleted.

### ACTION `crop_thumbnails_after_save_new_thumb`
This action is called after saving a thumbnail by the plugin.

Provided data:
* `$fullFilePath`
* `$imageSizeName`
* `$imageMetadata['sizes'][$imageSizeName]`


### FILTER `crop_thumbnails_after_save_new_thumb`
This filter is called for every image-size after the crop of the image has been performed. It contains all of the modified metadata. With `$imageMetadata['sizes'][$imageSizeName]` you may access the last changed value.

Parameters:
* `$imageMetadata` (filter result)
* `$imageSizeName`
* `$currentFilePathInfo`
* `$croppedWidth`
* `$croppedHeight`
* `$croppingInput` - you may want to use `$croppingInput->sourceImageId`


### FILTER `crop_thumbnails_before_update_metadata`

The filter is called right before the attachement metadata are saved.

Parameters:
* `$imageMetadata`
* `$input->sourceImageId` - the id of the attachement


### FILTER `crop_thumbnails_editor_printratio`

Filters the ratio that is printed in the modal-dialog. Based on this ratio a selection is possible.

Parameters:
* `$printRatio`
* `$imageSizeName`

The filter can be used if you have two image-sizes that have nearly the same ratio but are are slightly different. You can add the following code in the functions.php of your theme to adjust the ratio of one or more specified image-sizes.

CAUTION: use only when the ratios are really close.

```php
add_filter( 'crop_thumbnails_editor_printratio', 'my_crop_thumbnails_editor_printratio', 10, 2);
function my_crop_thumbnails_editor_printratio( $printRatio, $imageSizeName) {
	if($imageSizeName === 'strange-image-ratio') {
		$printRatio = '4:3';//do override ratio
	}
	return $printRatio;
}
```



### FILTER `crop_thumbnails_editor_jsonDataValues`

The filter can be used to adjust the json-data that are used by the editor.

Parameters:
* `$jsonDataValues`



### FILTER `crop_thumbnails_filename`

The filter can be used to change the fullpath with filename of the newly created thumbnail.

Parameters:
* `$destfilename` - The full path of the file
* `$file` - The original file
* `$w` - The width of the thumbnail size
* `$h` - The height of the thumbnail size
* `$crop` - If true the thumbnail size is cropped
* `$imageMetadata` - The Wordpress image-metadata array (added in version 1.2.6)



### FILTER `crop_thumbnails_image_sizes`

A filter to remove/adjust image-sizes used by the plugin. Use carefully, in most cases the settings-screen provide enough possibilities to adjust displaying of image-sizes.

Parameters:
* `$sizes`



### FILTER `image_size_names_choose` (wordpress core)

The filter is provided by wordpress and used by the plugin. You can use the filter to provide the human-readable name of a image-size.

Example
```php
add_filter( 'image_size_names_choose', 'my_custom_sizes' );
function my_custom_sizes( $sizes ) {
	$sizes = array_merge( $sizes, [
		'strange-size' => 'my new name',
		'abc' => 'alphabet',
	]);
	return $sizes;
}
```

### FILTER `crop_thumbnails_activate_on_adminpages`

Filter for adding/removing the plugins js/css files from a certain admin-page. If, for example, you have a ACF-driven image-input field on a taxonomy page. The crop thumbnails plugin will not work unless you add the following lines in your functions.php (else the js/css will not be included on the taxonomy edit-page and the plugin therefore can not work).

Example
```php
add_filter('crop_thumbnails_activate_on_adminpages', function($oldValue) {
	global $pagenow;
	return $oldValue || $pagenow==='term.php';
	//for adding taxonomy edit-page to the list of pages where crop-thumbnails work
});
```

### FILTER `crop_thumbnails_user_permission_check`

You can customize the user permissions, that are needed to crop the thumbnails by using this filter.

Example
```php
add_filter('crop_thumbnails_user_permission_check', function($oldValue, $imageId) {
	if($imageId===42) {
		return false;//never let this image be cropped
	}
	if(wp_get_current_user()->user_login==='Dipper Pines') {
		return true;
	}
	return $oldValue;
}, 10, 2);
```

### FILTER `crop_thumbnails_crop_data_image_sizes`

Filter vor changing the available image sizes in the frontend. You may use this in conjuction with `crop_thumbnails_active_image_sizes` to limit the image-sizes that can be cropped.


### FILTER `crop_thumbnails_active_image_sizes`

Filter for changing the available image sizes when processing the images. You may use this in conjuction with `crop_thumbnails_crop_data_image_sizes` to limit the image-sizes that can be cropped.