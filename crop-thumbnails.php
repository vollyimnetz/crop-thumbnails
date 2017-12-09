<?php
/**
 * Plugin name: Crop Thumbnails
 * Plugin URI: http://wordpress.org/extend/plugins/crop-thumbnails/
 * Author: Volkmar Kantor
 * Author URI: http://www.totalmedial.de
 * Version: 1.0.0

 * Description: Crop your thumbnails, the easy way to crop your custom image sizes.
 * Text Domain: cpt_lang
 *
 * License: GPL v3
 * Copyright 2012  Volkmar Kantor  (email : info@totalmedial.de)

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


define('CROP_THUMBS_VERSION','1.0.0');

//cpt - stands for crop-post-thumbnail
function cpt_plugin_init() {
	load_plugin_textdomain( 'cpt_lang', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	__('Crop your thumbnails, the easy way.');//have to be the same as the plugins-description - for automatic integration into poedit
}
add_action('plugins_loaded', 'cpt_plugin_init');

/**
 * returns the WpVersion in as a float
 */
function cptGetWpVersion() {
	$version = get_bloginfo('version');
	$version = floatval(substr($version,0,3));
	return $version;
}

include_once(dirname(__FILE__).'/functions/settings.php');
include_once(dirname(__FILE__).'/functions/editor.php');
include_once(dirname(__FILE__).'/functions/backendpreparer.php');
include_once(dirname(__FILE__).'/functions/save.php');
