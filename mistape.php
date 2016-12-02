<?php
/*
Plugin Name: Mistape
Description: Mistape allows visitors to effortlessly notify site staff about found spelling errors.
Version: 1.3.2
Author URI: https://deco.agency
Author: deco.agency
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: mistape
Domain Path: /languages
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MISTAPE__VERSION', '1.3.2' );
define( 'MISTAPE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MISTAPE__PLUGIN_FILE', __FILE__ );

require_once( MISTAPE__PLUGIN_DIR . 'src/class-deco-mistape-abstract.php' );
require_once( MISTAPE__PLUGIN_DIR . 'src/class-deco-mistape-admin.php' );
require_once( MISTAPE__PLUGIN_DIR . 'src/class-deco-mistape-ajax.php' );

register_activation_hook( __FILE__, 'Deco_Mistape_Admin::activation' );
register_deactivation_hook( __FILE__, 'Deco_Mistape_Admin::deactivate_addons' );

add_action( 'plugins_loaded', 'deco_mistape_init' );
function deco_mistape_init() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// load ajax-related class
		Deco_Mistape_Ajax::maybe_instantiate();
	} elseif ( is_admin() ) {
		// conditionally load admin-related class
		Deco_Mistape_Admin::get_instance();
	} else {
		// or frontend class
		require_once( MISTAPE__PLUGIN_DIR . 'src/class-deco-mistape-front.php' );
		Deco_Mistape::get_instance();
	}
}