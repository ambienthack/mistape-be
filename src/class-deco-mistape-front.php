<?php

class Deco_Mistape extends Deco_Mistape_Abstract {

	private static $instance;
	private $is_appropriate_post;

	function __construct() {

		if ( ! static::is_appropriate_useragent() ) {
			return;
		}

		parent::__construct();

		if ( $this->options['first_run'] == 'yes' ) {
			return;
		}

		// Load textdomain
		$this->load_textdomain();

		// actions
		add_action( 'wp_footer', array( $this, 'insert_dialog' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_load_scripts_styles' ) );

		// filters
		add_filter( 'the_content', array( $this, 'append_caption_to_content' ), 1 );

		// shortcode
		if ( $this->options['register_shortcode'] == 'yes' ) {
			add_shortcode( 'mistape', array( $this, 'render_shortcode' ) );
		}
	}

	public static function get_instance() {
		if (null === static::$instance) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Handle shortcode
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function render_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'format' => $this->options['caption_format'],
				'class'  => 'mistape_caption',
				'image'  => '',
				'text'   => $this->get_caption_text(),
			),
			$atts,
			'mistape'
		);

		if ( $atts['format'] == 'image' && ! empty( $this->options['caption_image_url'] ) || $atts['format'] != 'text' && ! empty( $atts['image'] ) ) {
			$imagesrc = $atts['image'] ? $atts['image'] : $this->options['caption_image_url'];
			$output   = '<div class="' . $atts['class'] . '"><img src="' . $imagesrc . '" alt="' . $atts['text'] . '"></div>';
		} else {
			$output = '<div class="' . $atts['class'] . '"><p>' . $atts['text'] . '</p></div>';
		}

		return $output;
	}

	/**
	 * Load scripts and styles - frontend
	 */
	public function front_load_scripts_styles() {

		if ( ! $this->is_appropriate_post() && $this->options['register_shortcode'] != 'yes' ) {
			return;
		}

		$this->enqueue_dialog_assets();
	}

	/**
	 * Add Mistape caption to post content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function append_caption_to_content( $content ) {
		if ( ( $format = $this->options['caption_format'] ) == 'disabled' ) {
			return $content;
		}

		if ( ! $this->is_appropriate_post() ) {
			return $content;
		}

		$output = '';

		$raw_post_content = get_the_content();

		// check if we really deal with post content
		if ( $content !== $raw_post_content ) {
			return $content;
		}

		if ( $format == 'text' ) {
			$logo = $this->options['show_logo_in_caption'] == 'yes' ? '<span class="mistape-link-wrap"><a href="' . $this->plugin_url . '" rel="nofollow" class="mistape-link mistape-logo"></a></span>' : '';
			// linebreak is necessary
			$output = "\n" . '<div class="mistape_caption"><p>' . $logo . $this->get_caption_text() . '</p></div>';
		} elseif ( $format == 'image' ) {
			$output = '<div class="mistape_caption"><img src="' . $this->options['caption_image_url'] . '" alt="' . esc_attr( $this->get_caption_text() ) . '"></div>';
		}

		$output = apply_filters( 'mistape_caption_output', $output, $this->options );

		return $content . $output;
	}

	/**
	 * Mistape dialog output
	 */
	public function insert_dialog() {

		if ( ! $this->is_appropriate_post() && $this->options['register_shortcode'] != 'yes' ) {
			return;
		}

		// dialog output
		$output = $this->get_dialog_html();

		echo apply_filters( 'mistape_dialog_output', $output, $this->options );
	}

	/**
	 * exit early if user agent is unlikely to behave reasonable
	 *
	 * @return bool
	 */
	public static function is_appropriate_useragent() {
		if ( static::wp_is_mobile() ) {
			return false;
		}

		// check for IE, save some resources avoiding regex
		if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE ' )
		     || false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'Trident/' )
		     || false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'Edge/' )
		) {
			return false;
		}

		return true;
	}

	public function is_appropriate_post() {

		if ( is_null( $this->is_appropriate_post ) ) {
			$result = false;

			// a bit inefficient logic is necessary for some illogical themes and plugins
			if ( ( ( is_single() && in_array( get_post_type(), $this->options['post_types'] ) )
			       || ( is_page() && in_array( 'page', $this->options['post_types'] ) ) ) && ! post_password_required()
			) {
				$result = true;
			}

			$this->is_appropriate_post = apply_filters( 'mistape_is_appropriate_post', $result );
		}

		return $this->is_appropriate_post;
	}


	/**
	 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
	 *
	 * @staticvar bool $is_mobile
	 *
	 * @return bool
	 */
	public static function wp_is_mobile() {
		static $is_mobile = null;

		if ( isset( $is_mobile ) ) {
			return $is_mobile;
		}

		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$is_mobile = false;
		} elseif ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // many mobile devices (all iPhone, iPad, etc.)
		           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false
		           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk/' ) !== false
		           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false
		           || strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false
		           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false
		           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false
		) {
			$is_mobile = true;
		} else {
			$is_mobile = false;
		}

		return $is_mobile;
	}
}