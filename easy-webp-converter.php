<?php
/**
 * Plugin Name:       Easy & Simple WebP Converter
 * Plugin URI:        https://github.com/harman-codes/easy-simple-webp-converter
 * Description:       Convert all media library images to WebP with a configurable quality (0-100) in one click.
 * Version:           1.1.0
 * Author:            Harman
 * Text Domain:       easy-webp-converter
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'EASYWEBP_ENTRY_FILE', __FILE__ );
define( 'EASYWEBP_ROOT_DIR', plugin_dir_path( __FILE__ ) );
define( 'EASYWEBP_ROOT_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'EASYWEBP_VERSION', '1.1.0' );

define( 'EASYWEBP_OPTION_QUALITY', 'easy_webp_quality' );
define( 'EASYWEBP_OPTION_AUTOCONVERT', 'easy_webp_autoconvert' );
define( 'EASYWEBP_OPTION_PENDING', 'easy_webp_pending_ids' );
define( 'EASYWEBP_OPTION_TOTAL', 'easy_webp_total_count' );

define( 'EASYWEBP_BATCH_SIZE', 5 );

require_once EASYWEBP_ROOT_DIR . 'inc/class-easy-webp-converter.php';

/**
 * Returns the single instance of the plugin.
 *
 * @return Easy_WebP_Converter
 */
function easy_webp_converter() {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Easy_WebP_Converter();
	}

	return $instance;
}

easy_webp_converter();
