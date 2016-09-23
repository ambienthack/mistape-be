<?php
/*
Plugin Name: Mistape
Description: Mistape allows visitors to effortlessly notify site staff about found spelling errors.
Version: 1.3.0
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

add_action( 'plugins_loaded', 'deco_mistape_init' );
function deco_mistape_init() {
	require_once(__DIR__ . '/src/class-deco-mistape-abstract.php');

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// load ajax-related class
		require_once(__DIR__ . '/src/class-deco-mistape-ajax.php');
		Deco_Mistape_Ajax::maybe_instantiate();
	}
	elseif ( is_admin() ) {
		// conditionally load admin-related class
		require_once(__DIR__ . '/src/class-deco-mistape-admin.php');
		$instance = Deco_Mistape_Admin::get_instance();
		register_activation_hook( __FILE__, array( $instance, 'activation' ) );
	} else {
		// or frontend class
		require_once(__DIR__ . '/src/class-deco-mistape-front.php');
		Deco_Mistape::get_instance();
	}
}