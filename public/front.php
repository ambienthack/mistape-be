<?php

class Deco_Mistape extends Abstract_Deco_Mistape {

	function __construct() {

		if ( ! $this->verify_useragent() ) {
			return;
		}

		parent::__construct();

		// actions
		add_action( 'wp_footer', 		  array( $this, 'insert_dialog' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_load_scripts_styles' ) );

		if ( $this->options['register_shortcode'] == 'yes' ) {
			add_shortcode( 'mistape', array( $this, 'render_shortcode' ) );
		}

		// filters
		add_filter( 'the_content', array( $this, 'append_caption_to_content' ), 1 );

	}

	/**
	 * Handle shortcode
	 *
	 * @param $atts
	 * @return string
	 */
	public function render_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'format' => $this->options['caption_format'],
				'class'  => 'mistape_caption',
				'image'  => '',
				'text'  => $this->caption_text,
			),
			$atts,
			'mistape'
		);

		if ( $atts['format'] == 'image' && !empty( $this->options['caption_image_url'] ) || $atts['format'] != 'text' && !empty( $atts['image'] ) ) {
			$imagesrc = $atts['image'] ? $atts['image'] : $this->options['caption_image_url'];
			$output = '<div class="' . $atts['class'] . '"><img src="' . $imagesrc . '" alt="' . $atts['text'] . '"></div>';
		} else {
			$output = '<div class="' . $atts['class'] . '"><p>' . $atts['text'] . '</p></div>';
		}

		return $output;
	}

	/**
	 * Load scripts and styles - frontend
	 */
	public function front_load_scripts_styles() {
		wp_enqueue_script( 'mistape-front', plugins_url( 'js/front.js', __FILE__ ), array( 'jquery' ), $this->version, true );

		$nonce = wp_create_nonce( 'mistape_report' );
		wp_localize_script(
			'mistape-front', 'mistapeArgs', array(
				'ajaxurl'		=> admin_url( 'admin-ajax.php' ),
				'strings' 		=> array(
					'message'		=> $this->caption_text,
					'success'		=> $this->success_text,
					'close'			=> $this->close_text,
				),
				'nonce' => $nonce,
			)
		);

		wp_enqueue_style( 'mistape-front', plugins_url( 'css/front.css', __FILE__ ), array(), $this->version );

		// modal
		wp_enqueue_script( 'mistape-front-modal-modernizr', plugins_url( 'js/modal/modernizr.custom.js', __FILE__ ), array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'mistape-front-modal-classie', plugins_url( 'js/modal/classie.js', __FILE__ ), array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'mistape-front-modal-dialogfx', plugins_url( 'js/modal/dialogFx.js', __FILE__ ), array( 'jquery' ), $this->version, true );

		wp_enqueue_style( 'mistape-front-modal-dialog', plugins_url( 'css/modal/dialog.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( 'mistape-front-modal-sandra', plugins_url( 'css/modal/dialog-sandra.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Add Mistape caption to post content
	 *
	 * @param $content
	 * @return string
	 */
	public function append_caption_to_content( $content ) {
		$output = '';

		if ( ( is_single() || is_page() ) && in_array( get_post_type(), $this->options['post_types'] ) ) {

			$raw_post_content = get_the_content();

			// check if we really deal with post content
			if ( $content !== $raw_post_content ) {
				return $content;
			}

			$format = $this->options['caption_format'];

			if ( $format == 'text' ) {
				$logo = $this->options['show_logo_in_caption'] == 'yes' ? '<p class="mistape-link-wrap"><a href="' . $this->plugin_url . '" class="mistape-link"></a></p>' : '';
				$output = '<div class="mistape_caption">' . $logo . '<p>';
				$output .= $this->caption_text . '</p></div>';
			} elseif ( $format == 'image' ) {
				$output = '<div class="mistape_caption"><img src="' . $this->options['caption_image_url'] . '" alt="' . $this->caption_text . '"></div>';
			}

			$output = apply_filters( 'mistape_caption_output', $output);

		}

		return $content . $output;
	}

	/**
	 * Mistape dialog output
	 */
	public function insert_dialog() {

		// get dialog args
		$strings = apply_filters( 'mistape_dialog_args', array(
			'title'		=> $this->dialog_title,
			'message'	=> $this->dialog_message,
			'close'		=> $this->close_text,
		) );

		// dialog output
		$output = '
		<div id="mistape_dialog" class="dialog">
			<div class="dialog__overlay"></div>
			<div class="dialog__content">
				<h2>' . $strings['title'] . '</h2>
				<h3>' . $strings['message'] . '</h3>
				<div><a class="action" data-dialog-close>' . $strings['close'] . '</a></div>
			</div>
		</div>';

		echo apply_filters( 'mistape_dialog_output', $output );
	}

	/**
	 * Delete settings on plugin uninstall
	 */
	public static function uninstall_cleanup() {
		delete_option('mistape_options');
		delete_option('mistape_version');
	}

	/**
	 * exit early if user agent is unlikely to behave reasonable
	 *
	 * @return bool
	 */
	public static function verify_useragent() {
		if ( wp_is_mobile() ) {
			return false;
		}

		// save some resources avoiding regex
		if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) ) {
			preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
			if ( count( $matches ) < 2 ){
				preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $_SERVER['HTTP_USER_AGENT'], $matches);
			}

			if ( count($matches) > 1 && $matches[1] < 11 ) {
				return false;
			}
		}

		return true;
	}
}