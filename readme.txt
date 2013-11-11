=== Crop-Thumbnails ===
Contributors: volkmar-kantor
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=volkmar%2ekantor%40gmx%2ede&lc=DE&item_name=Volkmar%20Kantor%20%2d%20totalmedial%2ede&item_number=crop%2dthumbnails&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: post-thumbnails, images, media library
Requires at least: 3.1
Tested up to: 3.7.1
Stable tag: trunk
License: GPL v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

"Crop Thumbnails" made it easy to get exacly that specific image-detail you want to show. Crop your images the simple way.

== Description ==

The plugin enhance functionality to crop your thumbnails individuell and simple. It add links on backend to enter all images you had attached to a post, page or custom-post.
In the Crop-Editor you can choose one or more (if they have the same ratio) imagesizes and cut-off the part of the image you want.

It is possible to filter the list of available image-sizes (in dependency to post-types) in the settings (Settings > Crop-Thumbnails).

== Installation ==

You can use the built in installer and upgrader, or you can install the plugin manually.

1. You can either use the automatic plugin installer or your FTP program to upload it to your wp-content/plugins directory the top-level folder. Don't just upload all the php files and put them in /wp-content/plugins/.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure any settings from "Settings > Crop-Thumbnails".
4. Use it.

== Frequently Asked Questions ==

= What languages are supported? =
* English
* German (de_DE)
* brazilian portuguese (pt_BR) - thanks to Alex Meusburger
* Ukrainian (uk) - thanks to Jurko Chervony from www.skinik.name

= What internal rules use the plugin for cropping? =
* The plugin will only crop image-sizes where crop is set to "true" (hard crop mode - see: http://codex.wordpress.org/Function_Reference/add_image_size).
* If you had set one of image dimension in add_image_size to "0" or "9999" (an set crop to true) the plugin will crop it in the ratio of the original image.
* You are able to crop all images with the same ratio at once (default) or and any imagesize (and ratio) seperate.

= I have cropped the image but the old one is used on the page. =
If you had viewed your image on the site before, your browser has cached the image. Go tell them to reload the fresh image from the server by hitting "F5".

= I have cropped the image but in the featured-image box in the editor the croped version dont show up =
Refresh or Update the editor page.  
Note that the image in the featured-image box will only be a preview, to show what image is choosed. The theme defines what image-size is used on the front-end. 

= Is it possible to crop an non-cropped image-size? =
Currently not.

= Is it possible to adjust the css-style of the crop-thumbnail window? =
Yes, for a simple test, copy the css/cpt-window.css file into your template_directory and make some change.
Then add this code into the functions.php of your template.

`add_filter('crop_post_thumbnail_window_css','myCustomStyle');
function myCustomStyle($content) {
	$content = get_bloginfo('template_directory').'/cpt-window.css';
	return $content;
}`

== Screenshots ==

1. You have access to Crop-Thumbnails on post / page / custom-post-types.
2. All images attached to this post, will shown in a overlay. You have to choose the one you want to crop.
3. Choose one or more images (with the same ratio).
4. Crop-Thumbnails is also integrated in the media library.
5. Choose what image-sizes should be hidden (for what post-types), for better usability.

== Changelog ==
= 0.8.4 =
* fix a bug that may occure on some systems with xdebug enabled and low xdebug.max_nesting_level (see: http://wordpress.org/support/topic/error-when-trying-to-crop-a-certain-image)
* add dutch language (thanks to Max Gruson)

= 0.8.3 =
* fix a bug for systems with comma as dezimal seperator (http://wordpress.org/support/topic/doesnt-save-the-cropped-image-anywhere?replies=12#post-4563377)
* reduce capabilities from "edit_pages" AND "upload_files" to only "upload_files"
* more informations logged in the console if an error occurs while saving

= 0.8.2 =
* add filter for customize the style of the crop-thumbnail content ('crop_post_thumbnail_window_css')
* add a fix for dynamic height/width images (http://wordpress.org/support/topic/dynamic-widthheight)
* add ukrainian language (thanks to Jurko Chervony from www.skinik.name)

= 0.8.1 =
* fix warning: when settings are saved

= 0.8.0 =
* change Constant from CPT_LANG to CROP_THUMBS_LANG
* change Constant from CPT_VERSION to CROP_THUMBS_VERSION
* bug fix: wrong calculated scale in the cpt-crop.js (selection will again always fill the maximum space)
* change behavior: on landscape-ratio-images the selection will be initial in the middle of the image (portrait-ratio-images stay the same - i asume that portrait-ratio images are mostly portraits)
* add current jcrop (version v0.9.12) directly into the plugin to get rid of the subversion of cpt-crop.js for an prior version of jcrop in wordpress 3.4
* add settings-option to display debug (js and/or data)
* handle image-sizes with zero width or height
* fix notices: not set variables
* fix warnings: if an image-size is zero

= 0.7.2 =
* bug fix: change the way the link in the featured Image-Box is set
* languages: some adjustments
 
= 0.7.1 =
* add language: brazilian portuguese (pt_br)
* bug fix: fixes for the upcoming Wordpress 3.5
* bug fix: in crop editor, if an original image is larger than a single selected image-size, the predefined selection will now be on maximum possible size
* bug fix: ensure that other plugins can´t add styles and scripts into the crop-thumbnail-iframe 

= 0.7.0 =
* workflow-enhancement: change the way the plugin handled same image-ratios (faster editing and less warnings)
* the image-size with the biggest dimensions now is used for the min-boundaries (jcrop)
* if the min-boundaries (jcrop) are bigger than original image the min-boundaries turned off

= 0.6.0 = 
* add a settings link in the plugin-listing
* add a support-author area in the settings
* update language files
* Fix the readme-file for correct display informations on wordpress.org
* add screenshots for wordpress.org
* add license.txt

= 0.5.0 =
* Initial Version
 