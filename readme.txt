=== Crop-Thumbnails ===
Contributors: volkmar-kantor
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=volkmar%2ekantor%40gmx%2ede&lc=DE&item_name=Volkmar%20Kantor%20%2d%20totalmedial%2ede&item_number=crop%2dthumbnails&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: post-thumbnails, images, media library
Requires at least: 3.1
Tested up to: 3.4
Stable tag: trunk
License: GPL v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

"Crop Thumbnails" made it easy to get exacly that specific image-detail you want to show. Crop your images the simple way.

== Description ==

The plugin enhance functionality to crop your thumbnails individuell and simple. It add links on backend to enter all images you had attached to a post, page or custom-post.
In the Crop-Editor you can choose one or more (if they have the same ratio) image-sizes and cut-off the part of the image you want.

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
* German

= I have cropped the image but the old one is used on the page. =
If you had viewed your image on the site before, your browser has cached the image. Go tell them to reload the fresh image from the server by hitting "F5".

= Is it possible to crop an non-cropped image-size? =
Currently not.

= What ideas for further releases you have? =
* allow cropping window in frontside
* allow not cropped image-sizes
* allow new crop sizes beside the ones added with the add_image_size() function
* reset standard image
* write test cases
* currently the last selected image-size in the crop-editor decides how big the min-boundaries are --> the one with the biggest dimmensions have to use for the min-boundaries

== Screenshots ==

1. You have access to Crop-Thumbnails on post / page / custom-types.
2. All images attached to this post. You have to choose the one you want to crop.
3. Choose one or more images (with the same ratio).
4. Crop-Thumbnails is also integrated in the media library.
5. Choose what image-sizes should be hidden on what post-types, for better usability.

== Changelog ==

= 0.6.0 = 
* add a settings link in the plugin-listing
* add a support-author area in the settings
* update language files
* Fix the readme-file for correct display informations on wordpress.org
* add screenshots for wordpress.org
* add license.txt

= 0.5.0 =
* Initial Version
 