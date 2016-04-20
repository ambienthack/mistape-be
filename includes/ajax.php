<?php

class Deco_Mistape_Ajax extends Abstract_Deco_Mistape {

	private static $selection;
	private static $word;
	private static $context;
	private static $replace_context;
	private static $comment;

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// actions
		add_action( 'after_setup_theme',                    array( $this, 'init' ) );
		// frontend
		add_action( 'wp_ajax_mistape_report_error',         array( $this, 'ajax_process_report' ) );
		add_action( 'wp_ajax_nopriv_mistape_report_error',  array( $this, 'ajax_process_report' ) );
		// admin preview
		add_action( 'wp_ajax_mistape_preview_dialog',       array( $this, 'ajax_update_admin_dialog' ) );

		// sanitize $_POST
		self::$selection = isset( $_POST['selection'] ) ? sanitize_text_field( $_POST['selection'] ) : '';
		self::$word = isset( $_POST['word'] ) ? sanitize_text_field( $_POST['word'] ) : '';
		self::$context = isset( $_POST['context'] ) ? sanitize_text_field( $_POST['context'] ) : '';
		self::$replace_context = isset( $_POST['replace_context'] ) ? sanitize_text_field( $_POST['replace_context'] ) : '';
		self::$comment = isset( $_POST['comment'] ) ? sanitize_text_field( $_POST['comment'] ) : '';
	}

	/**
	 * Load plugin defaults
	 */
	public function init() {
		$this->recipient_email = $this->get_recipient_email();
	}

	/**
	 * Handle AJAX reports
	 */
	public function ajax_process_report() {

		if ( self::$selection ) {
			// check transients for repeated reports from IP
			$trans_name_short = 'mistape_short_ip_' . $_SERVER['REMOTE_ADDR'];
			$trans_name_long = 'mistape_long_ip_' . $_SERVER['REMOTE_ADDR'];
			$trans_5min = get_transient( $trans_name_short );
			$trans_30min = get_transient( $trans_name_long );
			$trans_5min = is_numeric( $trans_5min ) ? (int) $trans_5min : 0;
			$trans_30min = is_numeric( $trans_30min ) ? (int) $trans_30min : 0;

			if ( $trans_5min > 5 || $trans_30min > 30 ) {
				wp_send_json_error( $this->get_dialog_html( array(
					'wrap'    => false,
					'mode'    => 'notify',
					'title'   => __( 'Report not sent', 'mistape' ),
					'message' => __( 'Spam protection: too many reports from your IP address.', 'mistape' ),
				) ) );
			}
			else {
				$trans_5min++;
				$trans_30min++;

				set_transient( $trans_name_short, $trans_5min, 300 );
				set_transient( $trans_name_long,  $trans_30min, 1800 );

				if ( self::$context && self::$replace_context && self::$word
				     && false !== strpos( self::$word, self::$selection )
				     && false !== strpos( self::$replace_context, self::$word )
				) {

					$text_inner = str_replace( $this::$selection, '<strong style="color: #C94E50;">' . self::$selection . '</strong>', self::$word );
					$text_outer = str_replace( self::$replace_context, '<span style="background-color: #EFEFEF;">' . $text_inner . '</span>', self::$word );
					$reported_text = str_replace( self::$replace_context, $text_outer, self::$context );
				}
				elseif ( isset( self::$context, self::$word ) && false !== strpos( self::$context, self::$word ) ) {
					$reported_text = str_replace( self::$word, '<strong style="color: #C94E50; background-color: #EFEFEF;">' . self::$word . '</strong>', self::$context );
				}
				else {
					$reported_text = self::$selection;
				}

				do_action( 'mistape_process_report', $reported_text, $_POST['context'] );

				$url = wp_get_referer();
				$post_id = url_to_postid( $url );
				$user = wp_get_current_user();

				$to = $this->recipient_email;
				$subject = __( 'Spelling error reported' , 'mistape' );

				// referrer
				$message = '<p>' . __( 'Reported from page:' , 'mistape' ) . ' ';
				$message .= !empty( $url ) ? '<a href="' . $url . '">' . urldecode( $url ) . '</a>' : _x( 'unknown' , '[Email] Reported from page: unknown', 'mistape' );
				$message .= "</p>\n";

				// post edit link
				if( $post_id ) {
					if ( $this->options['email_recipient']['post_author_first'] == 'yes' ) {
						$post_author_id = get_post_field( 'post_author', $post_id );
						// override default email recipient with post author's one
						$to = get_the_author_meta( 'user_email', $post_author_id );
					}
					if ( $edit_post_link = $this->get_edit_post_link( $post_id, 'raw' ) ) {
						$message .= '<p>' . __( 'Post edit URL:', 'mistape' ) . ' <a href="' . $edit_post_link . '">' . $edit_post_link . "</a></p>\n";
					}
				}

				// reported by
				if( $user->ID ) {
					$message .= '<p>' . __( 'Reported by:' , 'mistape' ) . ' ' . $user->display_name. ' (<a href="mailto:' . $user->data->user_email . '">' . $user->data->user_email . "</a>)</p>\n";
				}
				// reported text
				$message .= '<h3>' . __( 'Reported text' , 'mistape' ) . ":</h3>\n";
				$message .= '<div style="padding: 8px; border: 1px solid #eee; font-size: 18px; line-height: 26px"><code>' . $reported_text . "</code></div>\n";

				if ( self::$comment ) {
					$message .= '<h3>' . __( 'Comment:', 'mistape' ) . "</h3>\n";
					$message .= '<div style="padding: 8px; border: 1px solid #eee; font-size: 14px; line-height: 20px">' . self::$comment . "</div>\n";
				}

				$headers = array('Content-Type: text/html; charset=UTF-8');

				$to = apply_filters( 'mistape_mail_recipient', $to, $url, $user );
				$subject = apply_filters( 'mistape_mail_subject', $subject, $url, $user );
				$message = apply_filters( 'mistape_mail_message', $message, $url, $user );

				$result = wp_mail( $to, $subject, $message, $headers );

				if ( $result ) {
					wp_send_json_success( $this->get_dialog_html( array(
						'wrap' => false,
						'mode' => 'notify',
						'title'	  => __( 'Thanks!', 'mistape' ),
						'message' => __( 'Our editors are notified.', 'mistape' ),
					) ) );
				}
				else {
					wp_send_json_error( $this->get_dialog_html( array(
						'wrap'    => false,
						'mode'    => 'notify',
						'title'   => __( 'Report not sent', 'mistape' ),
						'message' => __( "A problem occurred while trying to deliver your report. That's all we know.", 'mistape' ),
					) ) );
				}
			}
		}

		wp_send_json_error( $this->get_dialog_html( array(
			'wrap' => false,
			'mode' => 'notify',
			'title'	  => __( 'Report not sent', 'mistape' ),
			'message' => __( 'Security error. Please refresh the page and try again.', 'mistape' ),
		) ) );
	}

	public function ajax_update_admin_dialog() {

		if ( !empty( $_POST['mode'] ) ) {
			$args = array(
				'mode' => $_POST['mode'],
				'reported_text_preview' => 'Lorem <span class="mistape_mistake_highlight">upsum</span> dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
			);
			wp_send_json_success( $this->get_dialog_html( $args ) );
		}

		wp_send_json_error();
	}

	/**
	 * duplicate of original WP function excluding user capabilities check
	 * @param int $id
	 * @param string $context
	 *
	 * @return mixed|null|void
	 */
	public static function get_edit_post_link( $id = 0, $context = 'display' ) {
		if ( ! $post = get_post( $id ) )
			return null;

		if ( 'revision' === $post->post_type )
			$action = '';
		elseif ( 'display' == $context )
			$action = '&amp;action=edit';
		else
			$action = '&action=edit';

		$post_type_object = get_post_type_object( $post->post_type );
		if ( !$post_type_object )
			return null;

		// this part of original WP function is commented out
		/*if ( !current_user_can( 'edit_post', $post->ID ) )
			return;*/

		return apply_filters( 'get_edit_post_link', admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) ), $post->ID, $context );
	}
}