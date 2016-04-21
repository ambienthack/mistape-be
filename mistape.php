<?php
/*
Plugin Name: Mistape
Description: Mistape allows visitors to effortlessly notify site staff about found spelling errors.
Version: 1.1.3
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// load ajax-related class
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	require_once( __DIR__ . '/includes/ajax.php' );
	$Mistape_Ajax = new Deco_Mistape_Ajax();
} // conditionally load admin-related class
elseif ( is_admin() ) {
	require_once( __DIR__ . '/includes/admin.php' );
	$Mistape_Admin = new Deco_Mistape_Admin();
	register_activation_hook( __FILE__, array( $Mistape_Admin, 'activation' ) );
	register_uninstall_hook( __FILE__, array( 'Abstract_Deco_Mistape', 'uninstall_cleanup' ) );
} else {
	// or frontend class
	require_once( __DIR__ . '/includes/front.php' );
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
		'email_recipient'       => array(
			'type'              => 'admin',
			'id'                => '1',
			'email'             => '',
			'post_author_first' => 'yes'
		),
		'post_types'            => array(),
		'register_shortcode'    => 'no',
		'caption_format'        => 'text',
		'caption_text_mode'     => 'default',
		'custom_caption_text'   => '',
		'dialog_mode'           => 'confirm',
		'caption_image_url'     => '',
		'show_logo_in_caption'  => 'yes',
		'first_run'             => 'yes',
		'multisite_inheritance' => 'no'
	);
	protected $version = '1.1.3';
	protected $plugin_path = __FILE__;
	protected $plugin_url = 'http://mistape.com';
	protected $recipient_email = null;
	protected $email_recipient_types = array();
	protected $caption_formats = array();
	protected $dialog_modes = array();
	protected $post_types = array();
	protected $options = array();
	protected $default_caption_text = null;
	protected $caption_text = null;
	protected $caption_text_modes = null;
	protected $success_text = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		// settings
		$options       = get_option( 'mistape_options', $this->defaults );
		$options       = is_array( $options ) ? $options : array();
		$this->options = apply_filters( 'mistape_options', array_merge( $this->defaults, $options ) );

		// actions
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'after_setup_theme', array( $this, 'load_defaults' ) );
	}

	/**
	 * Load plugin defaults
	 */
	public function load_defaults() {
		$this->default_caption_text = __( 'If you have found a spelling error, please, notify us by selecting that text and pressing <em>Ctrl+Enter</em>.', 'mistape' );
		$this->caption_text         = apply_filters( 'mistape_caption_text',
			$this->options['caption_text_mode'] == 'custom' && isset( $this->options['custom_caption_text'] ) ? $this->options['custom_caption_text'] : $this->default_caption_text
		);
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
		if ( $this->options['email_recipient']['post_author_first'] == 'yes' && $post_id = url_to_postid( $_SERVER['HTTP_REFERER'] ) ) {
			$post = get_post( $post_id );
			$author_id = $post->post_author;
			$email = get_the_author_meta( 'user_email', $author_id );
		}
		else {
			if ( $this->options['email_recipient']['type'] == 'other' && $this->options['email_recipient']['email'] ) {
				$email = $this->options['email_recipient']['email'];
			} elseif ( $this->options['email_recipient']['type'] != 'other' && $this->options['email_recipient']['id'] ) {
				$email = get_the_author_meta( 'user_email', $this->options['email_recipient']['id'] );
			} else {
				$email = get_bloginfo( 'admin_email' );
			}
		}

		return $email;
	}

	/**
	 * Mistape dialog output
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function get_dialog_html( $args = array() ) {

		$mode     = isset( $args['mode'] ) ? $args['mode'] : $this->options['dialog_mode'];
		$defaults = array(
			'wrap'    => true,
			'mode'    => $mode,
			'title'   => __( 'Thanks!', 'mistape' ),
			'message' => __( 'Our editors are notified.', 'mistape' ),
			'close'   => __( 'Close', 'mistape' ),
		);

		if ( $mode != 'notify' ) {
			$defaults['reported_text']         = '';
			$defaults['context']               = '';
			$defaults['title']                 = __( 'Spelling error report', 'mistape' );
			$defaults['message']               = __( 'The following text will be sent to our editors:', 'mistape' );
			$defaults['reported_text_preview'] = '';
			$defaults['cancel']                = __( 'Cancel', 'mistape' );
			$defaults['send']                  = __( 'Send', 'mistape' );
		}

		$args = apply_filters( 'mistape_dialog_args', wp_parse_args( $args, $defaults ) );

		// begin
		$output = '';
		if ( $args['wrap'] ) {
			$output .= '<div id="mistape_dialog" data-mode="' . $args['mode'] . '"><div class="dialog__overlay"></div><div class="dialog__content">';
		}

		if ( $args['mode'] == 'notify' ) {
			$output .=
				'<div id="mistape_success_dialog" class="mistape_dialog_screen">' .
				'<h2>' . $args['title'] . '</h2>
				 <h3>' . $args['message'] . '</h3>
				 <div class="mistape_dialog_block">
				    <a class="mistape_action" data-dialog-close role="button">' . $args['close'] . '</a>
				 </div>
			 </div>';
		} else {
			$output .=
				'<div id="mistape_confirm_dialog" class="mistape_dialog_screen">' .
				'<h2>' . $args['title'] . '</h2>
					 <div class="mistape_dialog_block">' . '
						<h3>' . $args['message'] . '</h3>' . '
						<div id="mistape_reported_text">' . $args['reported_text_preview'] . '</div>
					 </div>';
			if ( $args['mode'] == 'comment' ) {
				$output .=
					'<div class="mistape_dialog_block">
				        <h3><label for="mistape_comment">' . __( 'Your comment (optional)', 'mistape' ) . ':</label></h3>
				        <textarea id="mistape_comment" cols="60" rows="3"></textarea>
			         </div>';
			}
			$output .=
				'<div class="mistape_dialog_block">
					<a class="mistape_action" data-action="send" role="button">' . $args['send'] . '</a>
					<a class="mistape_action" data-dialog-close role="button">' . $args['cancel'] . '</a>
				 </div>
				 <div class="pos-relative">
					 <div class="mistape_dialog_footer">
						powered by <a href="' . $this->plugin_url . '" rel="nofollow" class="mistape-link" target="_blank">Mistape</a>
					 </div>
				 </div>
			 </div>';
		}

		// end
		if ( $args['wrap'] ) {
			$output .= '</div></div>';
		}

		return $output;
	}
}