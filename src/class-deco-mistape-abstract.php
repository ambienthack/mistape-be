<?php

abstract class Deco_Mistape_Abstract {

	const DB_TABLE = 'mistape_reports';
	const IP_BANLIST_OPTION = 'mistape_ip_banlist';
	
	/**
	 * @var $defaults
	 */
	protected static $defaults = array(
		'email_recipient'       => array(
			'type'              => 'admin',
			'id'                => '1',
			'email'             => '',
			'post_author_first' => 'yes'
		),
		'post_types'               => array(),
		'register_shortcode'       => 'no',
		'caption_format'           => 'text',
		'caption_text_mode'        => 'default',
		'custom_caption_text'      => '',
		'dialog_mode'              => 'confirm',
		'caption_image_url'        => '',
		'show_logo_in_caption'     => 'yes',
		'first_run'                => 'yes',
		'multisite_inheritance'    => 'no',
		'plugin_updated_timestamp' => null,
	);
	protected static $abstract_constructed;
	protected static $supported_addons = array( 'mistape-table-addon' );
	protected static $plugin_path;
	public static $version = '1.2.0';
	public $plugin_url = 'http://mistape.com';
	public $recipient_email;
	public $email_recipient_types = array();
	public $caption_formats = array();
	public $dialog_modes = array();
	public $post_types = array();
	public $options = array();
	public $default_caption_text;
	public $caption_text;
	public $caption_text_modes;
	public $success_text;
	public $ip_banlist;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! self::$abstract_constructed ) {
			self::$plugin_path = dirname( dirname( __FILE__ ) ) . '/mistape.php';
			// settings
			$this->options = self::get_options();

			// actions
			do_action( 'mistape_init_addons', $this );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// plugin update
			add_action( 'upgrader_process_complete', array( $this, 'version_upgrade' ), 10, 2 );
		}
	}

	public static function get_options() {
		$options = get_option( 'mistape_options', self::$defaults );
		$options = is_array( $options ) ? $options : array();

		return apply_filters( 'mistape_options', array_merge( self::$defaults, $options ) );
	}

	public function get_caption_text() {
		if ( is_null( $this->caption_text ) ) {
			if ( $this->options['caption_text_mode'] == 'custom' && isset( $this->options['custom_caption_text'] ) ) {
				$text = $this->options['custom_caption_text'];
			} else {
				$text = $this->get_default_caption_text();
			}

			$this->caption_text = apply_filters( 'mistape_caption_text', $text );
		}

		return $this->caption_text;
	}

	public function get_default_caption_text() {
		if ( is_null( $this->default_caption_text ) ) {
			$this->default_caption_text = __( 'If you have found a spelling error, please, notify us by selecting that text and pressing <em>Ctrl+Enter</em>.',
				'mistape' );
		}

		return $this->default_caption_text;
	}

	/**
	 * Load textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mistape', false, dirname( plugin_basename( self::$plugin_path ) ) . '/languages' );
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
			'dry_run' => is_admin() ? '1' : '0',
		);

		if ( $mode == 'notify' ) {
			$defaults['title']   = __( 'Thanks!', 'mistape' );
			$defaults['message'] = __( 'Our editors are notified.', 'mistape' );
			$defaults['close']   = __( 'Close', 'mistape' );
		} else {
			$defaults['reported_text']         = '';
			$defaults['context']               = '';
			$defaults['title']                 = __( 'Spelling error report', 'mistape' );
			$defaults['message']               = __( 'The following text will be sent to our editors:', 'mistape' );
			$defaults['reported_text_preview'] = '';
			$defaults['cancel']                = __( 'Cancel', 'mistape' );
			$defaults['send']                  = __( 'Send', 'mistape' );
		}

		if ( $mode == 'comment' ) {
			$defaults['comment_label'] = __( 'Your comment (optional)', 'mistape' );
		}

		$args = apply_filters( 'mistape_dialog_args', wp_parse_args( $args, $defaults ) );

		// begin
		$output = '';
		if ( $args['wrap'] ) {
			$output .= '<div id="mistape_dialog" data-mode="' . esc_attr( $args['mode'] ) .
			           '" data-dry-run="' . esc_attr( (string) $args['dry_run'] ) . '">
			           <div class="dialog__overlay"></div><div class="dialog__content' .
			           ( $args['mode'] != 'comment' ? ' without-comment' : '' ) . '">';
		}

		if ( $args['mode'] == 'notify' ) {
			$output .=
				'<div id="mistape_success_dialog" class="mistape_dialog_screen">
					<div class="dialog-wrap">
						<h2>' . $args['title'] . '</h2>
						 <h3>' . $args['message'] . '</h3>
					</div>
					<div class="mistape_dialog_block">
					   <a class="mistape_action" data-dialog-close role="button">' . $args['close'] . '</a>
					</div>
				</div>';
		} else {
			$output .=
			   '<div id="mistape_confirm_dialog" class="mistape_dialog_screen">
					<div class="dialog-wrap">
						<div class="dialog-wrap-top">
							<h2>' . $args['title'] . '</h2>
							 <div class="mistape_dialog_block">' . '
								<h3>' . $args['message'] . '</h3>' . '
								<div id="mistape_reported_text">' . $args['reported_text_preview'] . '</div>
							 </div>
							 </div>
						<div class="dialog-wrap-bottom">';
			if ( $args['mode'] == 'comment' ) {
				$output .=
					'<div class="mistape_dialog_block comment">
				        <h3><label for="mistape_comment">' . $args['comment_label'] . ':</label></h3>
				        <textarea id="mistape_comment" cols="60" rows="3" maxlength="1000"></textarea>
			         </div>';
			}
			$output .=
					   '<div class="pos-relative">
							 <div class="mistape_dialog_footer">
								powered by <a href="' . $this->plugin_url . '" rel="nofollow" class="mistape-link" target="_blank">Mistape</a>
							 </div>
						</div>
					</div>
			    </div>
			    <div class="mistape_dialog_block">
					<a class="mistape_action" data-action="send" role="button">' . $args['send'] . '</a>
					<a class="mistape_action" data-dialog-close role="button" style="display:none">' . $args['cancel'] . '</a>
				</div>
				<div class="mistape-letter-front letter-part"></div>
				<div class="mistape-letter-back letter-part">
					<div class="mistape-letter-back-top"></div>
				</div>
				<div class="mistape-letter-top letter-part"></div>
			</div>';
		}

		// end
		if ( $args['wrap'] ) {
			$output .= '</div></div>';
		}

		return $output;
	}

	public static function get_formatted_reported_text( $selection, $word = null, $replace_context = null, $context = null ) {
		$word = $word ? $word : $selection;
		$replace_context = $replace_context ? $replace_context : $word;

		if ( $context && $replace_context && $word
		     && false !== strpos( $word, $selection )
		     && false !== strpos( $replace_context, $word )
		) {
			$text_inner    = str_replace( $selection, '<strong style="color: #C94E50;">' . $selection . '</strong>', $word );
			$text_outer    = str_replace( $replace_context, '<span style="background-color: #EFEFEF;">' . $text_inner . '</span>', $word );
			$result = str_replace( $replace_context, $text_outer, $context );
		} elseif ( isset( $context, $word ) && false !== strpos( $context, $word ) ) {
			$result = str_replace( $word, '<strong style="color: #C94E50; background-color: #EFEFEF;">' . $word . '</strong>', $context );
		} else {
			$result = $selection;
		}

		return $result;
	}

	public function is_ip_in_banlist( $ip ) {
		if ( $banlist = $this->get_ip_banlist() ) {
			if ( in_array( $ip, (array) $banlist ) ) {
				return true;
			}
		}

		return false;
	}

	public function get_ip_banlist() {
		if ( is_null( $this->ip_banlist ) ) {
			$this->ip_banlist = get_option( self::IP_BANLIST_OPTION, array() );
		}

		return $this->ip_banlist;
	}

	public function enqueue_dialog_assets() {
		// style
		wp_enqueue_style( 'mistape-front', plugins_url( 'assets/css/mistape-front.css', self::$plugin_path ), array(), self::$version );

		// modernizer
		wp_enqueue_script( 'modernizr', plugins_url( 'assets/js/modernizr.custom.js', self::$plugin_path ), array( 'jquery' ), self::$version, true );

		// frontend script (combined)
		wp_enqueue_script( 'mistape-front', plugins_url( 'assets/js/mistape-front.js', self::$plugin_path ), array( 'jquery', 'modernizr' ), self::$version, true );
		wp_localize_script( 'mistape-front', 'decoMistape', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	public static function create_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'mistape_reports';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			ID mediumint(9) unsigned NOT NULL auto_increment,
			post_id bigint(20) unsigned UNSIGNED,
			post_author bigint(20) UNSIGNED,
			reporter_user_id bigint(20) UNSIGNED,
			reporter_IP varchar(100) NOT NULL,
			date datetime NOT NULL default '0000-00-00 00:00:00',
			date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
			selection varchar(255) NOT NULL,
			selection_word varchar(255),
			selection_replace_context varchar(2000),
			selection_context varchar(2000),
			comment varchar(2000),
			url varchar(2083),
			agent varchar(255),
			language varchar(50),
  			status varchar(20) NOT NULL default 'pending',
  			token char(20),
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY post_author (post_author),
			KEY reporter_user_id (reporter_user_id),
			KEY date_gmt (date_gmt)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function version_upgrade( /** @noinspection PhpUnusedParameterInspection */ $upgrader_object, $options ) {
		if ( isset( $options['plugins'] ) || ! in_array( self::$plugin_path, $options['plugins'] ) ) {
			return;
		}

		$db_version = get_option( 'mistape_version', '1.0.0' );
		if ( version_compare( '1.2.0', $db_version ) === 1 ) {
			self::create_db();
		}
		if ( version_compare( self::$version, $db_version ) === 1 ) {
			update_option( 'mistape_version', self::$version, false );
		}
		
		$this->options['plugin_updated_timestamp'] = time();
		update_option( 'mistape_options', $this->options );
	}
}