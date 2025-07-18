<?php
namespace crop_thumbnails;

/**
 * Plugin name: Crop Thumbnails
 * Plugin URI: https://wordpress.org/extend/plugins/crop-thumbnails/
 * Author: Volkmar Kantor
 * Author URI: https://www.totalmedial.de
 * Version: 1.9.6
 * Description: The easy way to adjust your cropped image sizes.
 *
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

define('CROP_THUMBNAILS_VERSION','1.9.6');

include_once __DIR__.'/functions/enqueuejsmodule.php';
include_once __DIR__.'/functions/helper.php';
include_once __DIR__.'/functions/settingsscreen.php';
include_once __DIR__.'/functions/rest.cropping.php';
include_once __DIR__.'/functions/rest.settings.php';
include_once __DIR__.'/functions/editor.php';
include_once __DIR__.'/functions/backendpreparer.php';
include_once __DIR__.'/functions/save.php';
include_once __DIR__.'/functions/extend/WebPExpressExtension.php';
include_once __DIR__.'/functions/extend/ImagifyExtension.php';
