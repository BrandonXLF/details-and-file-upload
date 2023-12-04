<?php
/**
 * Details and File Upload for WooCommerce
 *
 * @package     Details and File Upload
 * @author      Brandon Fowler
 * @copyright   Brandon Fowler
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Details and File Upload for WooCommerce
 * Plugin URI: https://www.brandonfowler.me/details-and-file-upload/
 * Description: Add general or item-specific detail inputs and file uploads to the WooCommerce checkout page.
 * Version: 1.0.0
 * Requires at least: 4.6
 * Requires PHP: 7.0
 * Author: Brandon Fowler
 * Author URI: https://www.brandonfowler.me/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE', __FILE__ );

require 'src/includes/class-autoloader.php';

Autoloader::init();
Display::init();
Data_Hooks::init();
API::init();
Settings::init();