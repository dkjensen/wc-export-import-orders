<?php
/**
 *  Plugin Name: WooCommerce Order Export & Import
 *  Description: Simple interface to export orders with all order meta
 *  Version: 1.0.0
 *  Author: David Jensen
 *  Author URI: http://dkjensen.com
 *  Text Domain: wc-oei
 *  Domain Path: languages
**/


if( ! defined( 'WC_OEI_PLUGIN_DIR' ) ) {
	define( 'WC_OEI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'WC_OEI_PLUGIN_URL' ) ) {
	define( 'WC_OEI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if( ! defined( 'WC_OEI_VERSION' ) ) {
	define( 'WC_OEI_VERSION', '1.0.0' );
}


if( is_admin() ) {
	require_once WC_OEI_PLUGIN_DIR . 'includes/admin/wc-oei-importer-screen.php';
}

require_once WC_OEI_PLUGIN_DIR . 'includes/class-wc-oei-export-handler.php';
require_once WC_OEI_PLUGIN_DIR . 'includes/wc-oei-export.php';
