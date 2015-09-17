<?php

class Deco_Mistape_Admin extends Abstract_Deco_Mistape {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// strings
		$this->post_types = $this->get_enabled_post_types();

		// actions
		add_action( 'admin_init',				array( $this, 'register_settings' ) );
		add_action( 'admin_menu', 				array( $this, 'admin_menu_options' ) );
		add_action( 'admin_notices', 			array( $this, 'plugin_activated_notice' ) );
		add_action( 'after_setup_theme',		array( $this, 'load_defaults' ) );
		add_action( 'admin_enqueue_scripts',	array( $this, 'admin_load_scripts_styles' ) );

		// filters
		add_filter( 'plugin_action_links', 		array( $this, 'plugins_page_settings_link' ), 10, 2 );
	}

	/**
	 * Add submenu
	 */
	public function admin_menu_options() {
		add_options_page(
			'Mistape', 'Mistape', 'manage_options', 'mistape', array( $this, 'print_options_page' )
		);
	}

	/**
	 * Options page output
	 */
	public function print_options_page() {
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'configuration';

		?>
		<div class="wrap">
			<h2>Mistape</h2>
			<h2 class="nav-tab-wrapper">
			<?php
				printf( '<a href="%s" class="nav-tab%s" data-bodyid="mistape-configuration" >%s</a>',   admin_url( 'options-general.php?page=mistape&tab=configuration' ), $active_tab == 'configuration' ? ' nav-tab-active' : '', __( 'Configuration', 'mistape' ) );
				printf( '<a href="%s" class="nav-tab%s" data-bodyid="mistape-help">%s</a>',             admin_url( 'options-general.php?page=mistape&tab=help' ), $active_tab == 'help' ? ' nav-tab-active' : '', __( 'Help', 'mistape' ) );
			?>
			</h2>
			<?php printf( '<div id="mistape-configuration" class="mistape-tab-contents" %s>', $active_tab == 'configuration' ? '' : 'style="display: none;"' ); ?>
				<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
				<?php
					settings_fields( 'mistape_options' );
					do_settings_sections( 'mistape_options' );
				?>
					<p class="submit">
						<?php submit_button( '', 'primary', 'save_mistape_options', false ); ?>
					</p>
				</form>
			</div>
			<?php
				printf( '<div id="mistape-help" class="mistape-tab-contents" %s>', $active_tab == 'help' ? '' : 'style="display: none;" ' );
                $this->print_help_page();
			?>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Regiseter plugin settings
	 */
	public function register_settings() {
		register_setting( 'mistape_options', 'mistape_options', array( $this, 'validate_options' ) );

		add_settings_section( 'mistape_configuration', '', array( $this, 'section_configuration' ), 'mistape_options' );
		add_settings_field( 'mistape_email_recipient', 		__( 'Email recipient', 'mistape' ), 	array( $this, 'field_email_recipient' ), 		'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_post_types', 			__( 'Post types', 'mistape' ), 		    array( $this, 'field_post_types' ), 			'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_register_shortcode', 	__( 'Shortcodes', 'mistape' ), 		    array( $this, 'field_register_shortcode' ), 	'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_caption_format', 		__( 'Caption format', 'mistape' ), 	    array( $this, 'field_caption_format' ), 		'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_caption_text_mode', 	__( 'Caption text mode', 'mistape' ), 	array( $this, 'field_caption_text_mode' ), 		'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_show_logo_in_caption', __( 'Mistape Logo', 'mistape' ), 	    array( $this, 'field_show_logo_in_caption' ), 	'mistape_options', 'mistape_configuration' );
	}

	/**
	 * Section callback
	 */
	public function section_configuration() {}

	/**
	 * Email recipient selection
	 */
	public function field_email_recipient() {
		echo '
		<fieldset>';


		foreach ( $this->email_recipient_types as $value => $label ) {
			echo '
			<label><input id="mistape_email_recipient_type-' . $value . '" type="radio" name="mistape_options[email_recipient][type]" value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['email_recipient']['type'], false ) . ' />' . esc_html( $label ) . '</label><br />';
		}

		echo '
			<div id="mistape_email_recipient_list-admin"' . ($this->options['email_recipient']['type'] == 'admin' ? '' : 'style="display: none;"' ) . '>';

		echo'
			<select name="mistape_options[email_recipient][id][admin]">';

		$admins = $this->get_user_list_by_role( 'administrator' );
		foreach ( $admins as $user ) {
			echo '
				<option value="' . $user->ID . '" ' . selected( $user->ID, $this->options['email_recipient']['id'], false ) . '>' . esc_html( $user->user_nicename . ' (' . $user->user_email . ')' ) . '</option>';
		}

		echo '
			</select>
			</div>';

		echo '
			<div id="mistape_email_recipient_list-editor"' . ($this->options['email_recipient']['type'] == 'editor' ? '' : 'style="display: none;"' ) . '>';

		echo'
			<select name="mistape_options[email_recipient][id][editor]">';

		$editors = $this->get_user_list_by_role( 'editor' );
		if ( !empty( $editors ) ) {
			foreach ( $editors as $user ) {
				echo '
				<option value="' . $user->ID . '" ' . selected( $user->ID, $this->options['email_recipient']['id'], false ) . '>' . esc_html( $user->user_nicename . ' (' . $user->user_email . ')' ) . '</option>';
			}
		}
		else {
			echo '
			<option value="empty" ' . selected( 'empty', $this->options['email_recipient']['id'], false ) . '>' . __( '-- no editors found --', 'mistape' ) . '</option>';
		}

		echo '
			</select>
			</div>
			<div id="mistape_email_recipient_list-other" ' . ($this->options['email_recipient']['type'] == 'other' ? '' : 'style="display: none;"' ) . '>
				<input type="text" class="regular-text" name="mistape_options[email_recipient][email]" value="' . esc_attr( $this->options['email_recipient']['email'] ) . '" />
				<p class="description">' . __('separate multiple recipients with commas', 'mistape') . '</p>
			</div>
		</fieldset>';
	}

	/**
	 * Post types to show caption in
	 */
	public function field_post_types() {
		echo '
		<fieldset style="max-width: 600px;">';

		foreach ( $this->post_types as $value => $label) {
			echo '
			<label style="padding-right: 8px; min-width: 60px;"><input id="mistape_post_type-' . $value . '" type="checkbox" name="mistape_options[post_types][' . $value . ']" value="1" '. checked( true, in_array( $value, $this->options['post_types'] ), false ) . ' />' . esc_html( $label ) . '</label>	';
		}

		echo '
			<p class="description">' . __( '"Press Ctrl+Enter&hellip;" captions will be displayed at the bottom of selected post types.', 'mistape' ) . '</p>
		</fieldset>';
	}

	/**
	 * Shortcode option
	 */
	public function field_register_shortcode() {
		echo '
		<fieldset>
			<label><input id="mistape_register_shortcode" type="checkbox" name="mistape_options[register_shortcode]" value="1" ' . checked( 'yes', $this->options['register_shortcode'], false ) . '/>' . __( 'Register <code>[mistape]</code> shortcode.', 'mistape' ) . '</label>
			<p class="description">' . __( 'Enable if manual caption insertion via shortcodes is needed.', 'mistape' ) . '</p>
			<p class="description">' . __( 'Usage examples are in Help section.', 'mistape' ) . '</p>
		</fieldset>';
	}

	/**
	 * Caption format option
	 */
	public function field_caption_format() {
		echo '
		<fieldset>';

		foreach ( $this->caption_formats as $value => $label ) {
			echo '
			<label><input id="mistape_caption_format-' . $value . '" type="radio" name="mistape_options[caption_format]" value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['caption_format'], false ) . ' />' . esc_html( $label ) . '</label><br />';
		}

		echo '
		<div id="mistape_caption_image"' . ( $this->options['register_shortcode'] == 'yes' || $this->options['caption_format'] === 'image' ? '' : 'style="display: none;"' ) . '>
			<p class="description">' . __( 'Enter the full image URL starting with http://', 'mistape' ) . '</p>
			<input type="text" class="regular-text" name="mistape_options[caption_image_url]" value="' . esc_attr( $this->options['caption_image_url'] ) . '" />
		</div>
		</fieldset>';
	}

	/**
	 * Caption custom text field
	 */
	public function field_caption_text_mode() {
		echo '
		<fieldset>';

		foreach ( $this->caption_text_modes as $value => $label ) {
			echo '<label><input id="mistape_caption_text_mode-' . $value . '" type="radio" name="mistape_options[caption_text_mode]" ' .
				'value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['caption_text_mode'], false ) . ' />' . $label['name'];
			echo empty( $label['description'] ) ? ':' : ' <span class="description">(' . $label['description'] . ')</span>';
			echo  '</label><br />';
		}

		$textarea_contents = !empty( $this->options['custom_caption_text'] ) ? $this->options['custom_caption_text'] : $this->default_caption_text;
		$textarea_state = $this->options['caption_text_mode'] == 'default' ? ' disabled="disabled"' : '';

		echo '<textarea id="mistape_custom_caption_text" name="mistape_options[custom_caption_text]" cols="70" rows="4"
			data-default="' . esc_attr( $this->default_caption_text ) . '"' . $textarea_state . ' />' . esc_textarea( $textarea_contents ) . '</textarea><br />
		</fieldset>';
	}

	/**
	 * Shortcode option
	 */
	public function field_show_logo_in_caption() {
		echo '
		<fieldset>
			<label><input id="mistape_show_logo_in_captione" type="checkbox" name="mistape_options[show_logo_in_caption]" value="1" ' . checked( 'yes', $this->options['show_logo_in_caption'], false ) . '/>' . __( 'Caption with Mistape logo', 'mistape' ) . '</label>
		</fieldset>';
	}

	/**
	* Validate options
	*
	* @param $input
	* @return mixed
    */
	public function validate_options( $input ) {

		if ( ! current_user_can( 'manage_options' ) )
			return $input;

		if ( isset( $_POST['save_mistape_options'] ) ) {

			// mail recipient
			$input['email_recipient']['type'] = sanitize_text_field( isset( $input['email_recipient']['type'] ) && in_array( $input['email_recipient']['type'], array_keys( $this->email_recipient_types ) ) ? $input['email_recipient']['type'] : $this->defaults['email_recipient']['type'] );

			if ( $input['email_recipient']['type'] == 'admin' && isset( $input['email_recipient']['id']['admin'] ) && ( user_can( $input['email_recipient']['id']['admin'], 'administrator' ) ) ) {
				$input['email_recipient']['id'] = $input['email_recipient']['id']['admin'];
			}
			elseif ( $input['email_recipient']['type'] == 'editor' && isset( $input['email_recipient']['id']['editor'] ) && ( user_can( $input['email_recipient']['id']['editor'], 'editor' ) ) ) {
				$input['email_recipient']['id'] = $input['email_recipient']['id']['editor'];
			}
			elseif ( $input['email_recipient']['type'] == 'other' && isset( $input['email_recipient']['email'] ) ) {
				$emails = explode( ',', str_replace( array(', ', ' '), ',', $input['email_recipient']['email'] ));
				$invalid_emails = array();
				foreach ( $emails as $key => &$email ) {
					if ( ! is_email( $email ) ) {
						$invalid_emails[] = $email;
						unset( $emails[$key] );
					}
					$email = sanitize_email( $email );
				}
				if ( $invalid_emails ) {
					add_settings_error(
						'mistape_options',
						esc_attr( 'invalid_recipient' ),
						sprintf( __( 'ERROR: You entered invalid email address: %s' , 'mistape' ), trim( implode( ',', $invalid_emails ), "," ) ),
						'error'
					);
				}

				$input['email_recipient']['email'] = trim( implode( ',', $emails ), "," );
				$input['email_recipient']['id'] = 0;
			}
			else {
				add_settings_error(
					'mistape_options',
					esc_attr( 'invalid_recipient' ),
					__( 'ERROR: You didn\'t select valid email recipient.' , 'mistape' ),
					'error'
				);
				$input['email_recipient'] = $this->options['email_recipient'];
			}

			// post types
			$input['post_types'] = isset( $input['post_types'] ) && is_array( $input['post_types'] ) && count( array_intersect( array_keys( $input['post_types'] ), array_keys( $this->post_types ) ) ) === count( $input['post_types'] ) ? array_keys( $input['post_types'] ) : array();

			// shortcode option
			$input['register_shortcode'] = (bool) isset( $input['register_shortcode'] ) ? 'yes' : 'no';

			// caption type
			$input['caption_format'] = isset( $input['caption_format'] ) && in_array( $input['caption_format'], array_keys( $this->caption_formats ) ) ? $input['caption_format'] : $this->defaults['caption_format'];
			if ( $input['caption_format'] === 'image' ) {
				if ( ! empty( $input['caption_image_url'] ) ) {
					$input['caption_image_url'] = esc_url( $input['caption_image_url'] );
				}
				else {
					add_settings_error(
						'mistape_options',
						esc_attr( 'no_image_url' ),
						__( 'ERROR: You didn\'t enter caption image URL.' , 'mistape'),
						'error'
					);
					$input['caption_format'] = $this->defaults['caption_format'];
					$input['caption_image_url'] = $this->defaults['caption_image_url'];
				}
			};

			// caption text mode
			$input['caption_text_mode'] = isset( $input['caption_text_mode'] ) && in_array( $input['caption_text_mode'], array_keys( $this->caption_text_modes ) ) ? $input['caption_text_mode'] : $this->defaults['caption_text_mode'];
			$input['custom_caption_text'] = $input['caption_text_mode'] == 'custom' && $input['custom_caption_text'] !== $this->default_caption_text ? wp_kses_post( $input['custom_caption_text'] ) : '';

			$input['show_logo_in_caption'] = $input['show_logo_in_caption'] === '1' ? 'yes' : 'no';

			$input['first_run'] = 'no';

		}

		return $input;
	}

	/**
	 * Add links to settings page
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return mixed
	 */
	public function plugins_page_settings_link( $links, $file ) {
		if ( ! current_user_can( 'manage_options' ) )
			return $links;

		$plugin = plugin_basename( $this->plugin_path );

		if ( $file == $plugin )
			array_unshift( $links, sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=mistape' ), __( 'Settings', 'mistape' ) ) );

		return $links;
	}

	/**
	 * Activate the plugin
	 */
	public function activation() {
		add_option( 'mistape_options', $this->defaults, '', 'no' );
		add_option( 'mistape_version', $this->version, '', 'no' );
	}

	/**
	 * Load scripts and styles - admin
     *
	 * @param $page
	 */
	public function admin_load_scripts_styles( $page ) {
		if ( $page !== 'settings_page_mistape' )
			return;

		wp_enqueue_script(
			'mistape-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version
		);
	}

	/**
	 * Add admin notice after activation if not configured
	 */
	public function plugin_activated_notice() {
		$wp_screen = get_current_screen();
		if ( $this->options['first_run'] == 'yes' && current_user_can( 'manage_options' ) ) {
			$html = '<div class="updated">';
			$html .= '<p>';
			if ( $wp_screen && $wp_screen->id == 'settings_page_mistape' ) {
				$html .= __( '<strong>Mistape</strong> settings notice will be dismissed after saving changes.', 'mistape' );
			}
			else {
				$html .= sprintf( __( '<strong>Mistape</strong> must now be <a href="%s">configured</a> before use.', 'mistape' ), admin_url( 'options-general.php?page=mistape' ) );
			}
			$html .= '</p>';
			$html .= '</div>';
			echo $html;
		}
	}

	/**
	 * Get admins list for options page
     *
	 * @param $role
	 *
	 * @return array
	 */
	public function get_user_list_by_role( $role ) {
		$users_query = get_users( array(
			'role' => $role,
			'fields' => array(
				'ID',
				'user_nicename',
				'user_email',
			),
			'orderby' => 'display_name'
		) );
		return $users_query;
	}

	/**
	 * Return an array of registered post types with their labels
	 */
	public function get_enabled_post_types() {
		$post_types = get_post_types(
			array( 'public' => true ),
			'objects'
		);

		$post_types_list = array();
		foreach ( $post_types as $id => $post_type ) {
			$post_types_list[$id] = $post_type->label;
		}

		return $post_types_list;
	}

	/**
	 * Echo Help tab contents
	 */
	private static function print_help_page() {
		?>
		<div class="card">
			<h3><?php _e( 'Shortcodes' , 'mistape' ) ?></h3>
			<h4><?php _e( 'Optional shortcode parameters are:' , 'mistape' ) ?></h4>
			<ul>
				<li><code>format</code> — <?php _e( "can be 'text' or 'image'" , 'mistape' ) ?></li>
				<li><code>class</code> — <?php _e( 'override default css class' , 'mistape' ) ?></li>
				<li><code>text</code> — <?php _e( 'override caption text' , 'mistape' ) ?></li>
				<li><code>image</code> — <?php _e( 'override image URL' , 'mistape' ) ?></li>
			</ul>
			<p><?php _e( 'When no parameters specified, general configuration is used.' , 'mistape' ) ?><br />
				<?php _e( 'If image url is specified, format parameter can be omitted.' , 'mistape' ) ?></p>
			<h4><?php _e( 'Shortcode usage example:' , 'mistape' ) ?></h4>
			<ul>
				<li><p><code>[mistape format="text" class="mistape_caption_sidebar"]</code></p></li>
			</ul>
			<h4><?php _e( 'PHP code example:' , 'mistape' ) ?></h4>
			<ul>
				<li><p><code>&lt;?php do_shortcode( '[mistape format="image" class="mistape_caption_footer" image="/wp-admin/images/yes.png"]' ); ?&gt;</code></p></li>
			</ul>
		</div>

		<div class="card">
			<h3><?php _e( 'Hooks' , 'mistape' ) ?></h3>

			<h4><?php _e( 'Actions:' , 'mistape' ) ?></h4>

			<h4><code>mistape_process_report</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'executes after Ctrl+Enter pressed and report validated, before sending email.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $reported_text, str $context | str</p>

			<h4><?php _e( 'Filters:' , 'mistape' ) ?></h4>

			<h4><code>mistape_caption_text</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to modify caption text globally.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $text </p>

			<h4><code>mistape_caption_output</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to modify the caption HTML before output.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $html</p>

			<h4><code>mistape_dialog_args</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to modify modal dialog strings.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> array $args </p>

			<h4><code>mistape_dialog_output</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to modify the modal dialog HTML before output.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $html </p>

			<h4><code>mistape_mail_recipient</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to change email recipient.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $recipient</p>

			<h4><code>mistape_mail_subject</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to change email subject.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $subject</p>

			<h4><code>mistape_mail_message</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to modify email message to send.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> str $message</p>

			<h4><code>mistape_options</code></h4>
			<p class="description"><?php _e( 'Description:' , 'mistape' ) ?> <?php _e( 'allows to modify global options array during initialization.' , 'mistape' ) ?></p>
			<p class="description"><?php _e( 'Parameters:' , 'mistape' ) ?> $options | arr</p>

		</div>
		<?php
	}
}