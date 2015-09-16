<?php

class Deco_Mistape_Ajax extends Abstract_Deco_Mistape {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();
		
		// actions
		add_action( 'wp_ajax_mistape_report_error', array( $this, 'ajax_handler' ) );
		add_action( 'wp_ajax_nopriv_mistape_report_error', array( $this, 'ajax_handler' ) );
	}

	/**
	 * Handle AJAX reports
	 */
	public function ajax_handler() {

		$result = false;

		if (   isset( $_POST['nonce'] )
		       && isset( $_POST['reported_text'] )
		       && wp_verify_nonce( $_POST['nonce'], "mistape_report")
		) {
			$reported_text = sanitize_text_field( $_POST['reported_text'] );
			$context = sanitize_text_field( $_POST['context'] );

			// check transients for repeated reports from IP
			$trans_name_short = 'mistape_short_ip_' . $_SERVER['REMOTE_ADDR'];
			$trans_name_long = 'mistape_long_ip_' . $_SERVER['REMOTE_ADDR'];
			$trans_5min = get_transient( $trans_name_short );
			$trans_30min = get_transient( $trans_name_long );
			$trans_5min = is_numeric( $trans_5min ) ? (int) $trans_5min : 0;
			$trans_30min = is_numeric( $trans_30min ) ? (int) $trans_30min : 0;

			if ( !empty( $reported_text ) && $trans_5min < 5 && $trans_30min < 30 ) {

				$trans_5min++;
				$trans_30min++;

				set_transient( $trans_name_short, $trans_5min, 300 );
				set_transient( $trans_name_long,  $trans_30min, 1800 );

				$reported_text = $this->format_reported_text( $reported_text, $context);

				do_action( 'mistape_process_report', $reported_text, $context );

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
				if( $post_id && $edit_post_link = $this->get_edit_post_link( $post_id, 'raw' ) ) {
					$message .= '<p>' . __( 'Post edit URL:', 'mistape' ) . ' <a href="' . $edit_post_link . '">' . $edit_post_link . "</a></p>\n";
				}

				// reported by
				if( $user->ID ) {
					$message .= '<p>' . __( 'Reported by:' , 'mistape' ) . ' ' . $user->display_name. ' (<a href="mailto:' . $user->data->user_email . '">' . $user->data->user_email . "</a>)</p>\n";
				}
				// reported text
				$message .= '<h3>' . __( 'Reported text' , 'mistape' ) . ":</h3>\n";
				$message .= '<code>' . $reported_text . "</code>\n";

				$headers = array('Content-Type: text/html; charset=UTF-8');

				$to = apply_filters( 'mistape_mail_recipient', $to);
				$subject = apply_filters( 'mistape_mail_subject', $subject);
				$message = apply_filters( 'mistape_mail_message', $message );

				$result = wp_mail( $to, $subject, $message, $headers );
			}

		}

		$response = json_encode( $result );

		die( $response );
	}

	/**
	 * duplicate of original WP function excluding user capabilities check
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

	/**
	 * Trim reported text to reasonable length, highlight reported part of context
	 *
	 * @param $reported_text
	 * @param null $context
	 *
	 * @return mixed
	 */
	public function format_reported_text( $reported_text, $context = null) {

		// check if context contains reported text
		if ( false !== ( $context_lead_chars = strpos( $context, $reported_text ) ) ) {

			// work in progress
			/*$context_lead = substr( $context, -$context_lead_chars );

			$context_trail_chars = strlen( $context ) - strlen( $reported_text ) - $context_lead_chars;
			$context_trail = substr( $context, -$context_trail_chars );

			if ( $context_trail_chars > 70 ) {
				$end_cut_pos = strstr( $context, ' ' );
				$context = substr( $context, 0 );
			}*/

			$output = str_replace( $reported_text, '<strong style="color: red;">' . $reported_text . '</strong>', $context );
		}
		// if not, just return reported text dumping context
		else {
			$output = $reported_text;
		}

		return $output;
	}
}