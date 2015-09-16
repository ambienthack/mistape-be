<?php
/*
Plugin Name: Mistape
Description: Mistape allows users to effortlessly notify site staff about found spelling errors.
Version: 1.0.3
Author URI: https://deco.agency
Author: deco.agency
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: mistape
Domain Path: /languages

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.


THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

// load ajax-related class
if ( defined('DOING_AJAX') && DOING_AJAX ) {
	require_once( __DIR__ . '/admin/ajax.php' );
	$Mistape_Ajax = new Deco_Mistape_Ajax();
}
// conditionally load admin-related class
elseif ( is_admin() ) {
	require_once( __DIR__ . '/admin/admin.php' );
	$Mistape_Admin = new Deco_Mistape_Admin();
	register_activation_hook( __FILE__, array( $Mistape_Admin, 'activation' ) );
	register_uninstall_hook( __FILE__, array( 'Abstract_Deco_Mistape', 'uninstall_cleanup' ) );
}
else {
	// or frontend class
	require_once( __DIR__ . '/public/front.php' );
	$mistape = new Deco_Mistape();
}

/**
 * Abstract_Deco_Mistape class
 *
 * @class Abstract_Deco_Mistape
 */
abstract class Abstract_Deco_Mistape {

	/**
	 * @var $defaults
	 */
	protected $defaults = array(
		'email_recipient'		 => array(
			'type' => 'admin',
			'id' => '1',
			'email' => '',
		),
		'post_types' 		  => array(),
		'register_shortcode'  => false,
		'caption_format'	  => 'text',
		'caption_text_mode'	  => 'default',
		'custom_caption_text' => '',
		'caption_image_url'	  => '',
		'show_logo_in_caption' => 'yes',
		'first_run'			  => 'yes'
	);
	protected $version			     = '1.0.3';
	protected $plugin_path		     = __FILE__;
	protected $plugin_url		     = 'https://wordpress.org/plugins/mistape/';
	protected $recipient_email	     = null;
	protected $email_recipient_types = array();
	protected $caption_formats	     = array();
	protected $post_types		     = array();
	protected $options 			     = array();
	protected $default_caption_text  = null;
	protected $caption_text		     = null;
	protected $caption_text_modes    = null;
	protected $dialog_title		     = null;
	protected $dialog_message	     = null;
	protected $success_text		     = null;
	protected $close_text		     = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		// settings
		$this->options = apply_filters( 'mistape_options', array_merge( $this->defaults, get_option( 'mistape_options', $this->defaults ) ) );

		// actions
		add_action( 'plugins_loaded', 			array( $this, 'load_textdomain' ) );
		add_action( 'after_setup_theme',		array( $this, 'load_defaults' ) );
	}

	/**
	 * Load plugin defaults
	 */
	public function load_defaults() {
		$this->recipient_email = $this->get_recipient_email();
		$this->email_recipient_types = array(
			'admin'		=> __( 'Administrator', 'mistape' ),
			'editor' 	=> __( 'Editor', 'mistape' ),
			'other' 	=> __( 'Specify other', 'mistape' )
		);

		$this->caption_formats = array(
			'text'	=> __( 'Text', 'mistape' ),
			'image' => __( 'Image', 'mistape' )
		);

		$this->caption_text_modes = array(
			'default' => array(
				'name' => __( 'Default', 'mistape' ),
				'description' => __( 'automatically translates to supported languages', 'mistape' )
			),
			'custom' => array(
				'name' => __( 'Custom text', 'mistape' ),
				'description' => ''
			)
		);

		$this->default_caption_text = __( 'If you have found a spelling error, please, notify us by selecting that text and pressing <em>Ctrl+Enter</em>.', 'mistape' );

		$this->caption_text = apply_filters( 'mistape_caption_text',
			$this->options['caption_text_mode'] == 'custom' && isset( $this->options['custom_caption_text'] ) ? $this->options['custom_caption_text'] : $this->default_caption_text
		) . '</p>';
		$this->dialog_title = __( 'Thanks!', 'mistape' );
		$this->dialog_message = __( 'Our editors are notified.', 'mistape' );
		$this->close_text = __( 'Close', 'mistape' );

		update_option( 'mistape_options', $this->options );
	}

	/**
	 * Load textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mistape', false, dirname( plugin_basename( $this->plugin_path ) ) . '/languages' );
	}

	/**
	 * Get default settings
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Get recipient email
	 */
	public function get_recipient_email() {
		if ( $this->options['email_recipient']['type'] == 'other' && $this->options['email_recipient']['email'] ) {
			$email = $this->options['email_recipient']['email'];
		}
		elseif ( $this->options['email_recipient']['type'] != 'other' && $this->options['email_recipient']['id'] ) {
			$email = get_the_author_meta( 'user_email', $this->options['email_recipient']['id'] );
		}
		else {
			$email = get_bloginfo( 'admin_email' );
		}

		return $email;
	}
}