<?php

class Deco_Mistape_Admin extends Deco_Mistape_Abstract {

	private static $instance;

	/**
	 * Constructor
	 */
	protected function __construct() {

		parent::__construct();

		// Load textdomain
		$this->load_textdomain();

		// admin-wide actions
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'plugin_activated_notice' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// if multisite inheritance is enabled, add corresponding action
		if ( is_multisite() && $this->options['multisite_inheritance'] === 'yes' ) {
			add_action( 'wpmu_new_blog', array( $this, 'activation' ) );
		}

		// Mistape page-specific actions
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'mistape_settings' ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_load_scripts_styles' ) );
			add_action( 'admin_footer', array( $this, 'insert_dialog' ) );
		}

		// filters
		add_filter( 'plugin_action_links', array( $this, 'plugins_page_settings_link' ), 10, 2 );
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'deactivate'
		     && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] == plugin_basename( self::$plugin_path )
		) {
			add_filter( 'option_active_plugins', array( $this, 'deactivate_addons' ) );
		}

		register_uninstall_hook( __FILE__, array( 'Abstract_Deco_Mistape', 'uninstall_cleanup' ) );
	}

	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Load plugin defaults
	 */
	public function init() {
		// init only once
		if ( $this->email_recipient_types ) {
			return;
		}

		$this->post_types            = $this->get_post_types_list();
		$this->email_recipient_types = array(
			'admin'  => __( 'Administrator', 'mistape' ),
			'editor' => __( 'Editor', 'mistape' ),
			'other'  => __( 'Specify other', 'mistape' )
		);

		$this->caption_formats = array(
			'text'     => __( 'Text', 'mistape' ),
			'image'    => __( 'Image', 'mistape' ),
			'disabled' => __( 'Do not show caption at the bottom of post', 'mistape' )
		);

		$this->caption_text_modes = array(
			'default' => array(
				'name'        => __( 'Default', 'mistape' ),
				'description' => __( 'automatically translated to supported languages', 'mistape' )
			),
			'custom'  => array(
				'name'        => __( 'Custom text', 'mistape' ),
				'description' => ''
			)
		);

		$this->dialog_modes = array(
			'notify'  => __( 'Just notify of successful submission', 'mistape' ),
			'confirm' => __( 'Show preview of reported text and ask confirmation', 'mistape' ),
			'comment' => __( 'Preview and comment field', 'mistape' ),
		);
	}

	/**
	 * Add submenu
	 */
	public function admin_menu() {
		if ( apply_filters( 'mistape_show_settings_menu_item', true, $this ) ) {
			add_options_page( 'Mistape', 'Mistape', 'manage_options', 'mistape_settings',
				array( $this, 'print_options_page' ) );
		}
	}

	/**
	 * Options page output
	 */
	public function print_options_page() {
		global $wpdb;
		$this->init();

		// show changelog only if less than one week passed since updating the plugin
		$show_changelog = time() - (int) $this->options['plugin_updated_timestamp'] < WEEK_IN_SECONDS;

		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'configuration';
		$table_exists = !! $wpdb->get_var(
			"SELECT COUNT(*) FROM information_schema.tables
			WHERE table_schema = '" . DB_NAME . "'
			AND table_name = '{$wpdb->prefix}mistape_reports' LIMIT 1"
		);
		$reports_count = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mistape_reports") : null;
		?>
		<div class="wrap">
			<h2>Mistape</h2>
			<h2 class="nav-tab-wrapper">
				<?php
				printf( '<a href="%s" class="nav-tab%s" data-bodyid="mistape-configuration" >%s</a>',
					add_query_arg( 'tab', 'configuration' ),
					$active_tab == 'configuration' ? ' nav-tab-active' : '', __( 'Configuration', 'mistape' ) );
				printf( '<a href="%s" class="nav-tab%s" data-bodyid="mistape-help">%s</a>',
					add_query_arg( 'tab', 'help' ),
					$active_tab == 'help' ? ' nav-tab-active' : '', __( 'Help', 'mistape' ) );
				?>
			</h2>
			<?php printf( '<div id="mistape-configuration" class="mistape-tab-contents" %s>',
				$active_tab == 'configuration' ? '' : 'style="display: none;"' ); ?>
			<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
				<?php
				settings_fields( 'mistape_options' );
				do_settings_sections( 'mistape_options' );
				?>
				<p class="submit">
					<?php submit_button( '', 'primary', 'save_mistape_options', false ); ?>
					<span class="description alignright">
						<?php printf(
							_x( 'Please %s our plugin', '%s = rate', 'mistape' ),
							'<a href="https://wordpress.org/support/view/plugin-reviews/mistape#postform" target="_blank">' .
							_x( 'rate', 'please rate our plugin', 'mistape' ) . '</a>'
						); ?>
					</span>
				</p>
				<div id="mistape-sidebar">
					<div id="deco_products" class="postbox deco-right-sidebar-widget">
						<h3 class="hndle">
							<span><?php printf( _x( "%s's products", "deco.agency's products", 'mistape' ), '<a class="decoagency" href="https://deco.agency">deco.agency</a>' ) ?> </span>
						</h3>
						<div class="inside">
							<a class="deco_button decomments" href="http://decomments.com/" target="_blank">
								<span>de:comments</span>
							</a>
							<a class="deco_button debranding" href="https://wordpress.org/plugins/debranding/" target="_blank">
								<span>de:branding</span>
							</a>
							<a class="deco_button deadblocker" href="http://deadblocker.com/" target="_blank">
								<span>deAdblocker</span>
							</a>
						</div>
					</div>
					<?php if ( $show_changelog ) { ?>
					<div id="mistape_info" class="postbox deco-right-sidebar-widget">
						<h3 class="hndle">
							<span>New in Mistape 1.2.0</span>
						</h3>
						<div class="inside">
							<ul>
								<li>New dialog box design (send action is now animated)</li>
								<li>Introduce database table for saving reports. Used for checking for duplicates—you will not get multiple reports about the same error anymore.</li>
								<li>Introduce support for addons.</li>
								<li>(for developers) arguments for "mistape_process_report" action were changed.</li>
								<li>lots of improvements under the hood.</li>
							</ul>
						</div>
					</div>
					<?php }
					if ( $reports_count ) { ?>
						<div id="mistape_statistics" class="postbox deco-right-sidebar-widget">
							<h3 class="hndle">
								<span><?php _e('Statistics', 'mistape'); ?></span>
							</h3>
							<div class="inside">
								<p>
									<?php
									_e('Reports received up to date:', 'mistape' );
									echo ' <strong>' . $reports_count . '</strong>';
									?>
								</p>
								<p>
									<?php _e( 'Detailed mistake statistics is coming soon!', 'mistape' ); ?>
							</div>
						</div>
					<?php } ?>
				</div>
				<style scoped>
					#mistape-configuration form{
						position: relative;
					}
					#mistape-configuration form table{
						width: calc(100% - 310px);
					}
					#mistape-sidebar{
						position: absolute;
						top: 22px;
						right: 20px;
					}
					.deco-right-sidebar-widget{
						min-width: 235px;
						margin-bottom: 15px;
						padding: 0 15px;
						width: 150px;
						/*top: 50px;*/
					}
					.deco-right-sidebar-widget .hndle{
						cursor: auto!important;
					}
					.deco-right-sidebar-widget .decoagency{
						font-size: 16px;
						text-decoration: none;
						font-weight: bold;
					}
					.deco_button{
						padding: 4px;
						display: block;
						text-decoration: none;
						border: none;
					}
					.deco_button span{
						display: inline-block;
						vertical-align: middle;
						padding-bottom: 1px;
					}
					.deco_button span{
						margin-right: 6px;
						padding-left: 22px;
					}
					.deco_button.decomments {
						background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAAUCAMAAAC3SZ14AAAARVBMVEUAAADmuR7muR7muR7muR7muR7muR7muR7muR7muR7////57sfuz2XovSzsy1bz3I/v03Ppwjr89uT68tb04Z3x2IHrxkhmcNVUAAAACXRSTlMAwGCA8ODQsBB2dB4iAAAAc0lEQVQY043PWQrDMAxF0dQdnyY74/6XWqU1IZEJ5H7o44AE6hJCqUPTOUlpiDlQb5GEiLgfiCY1/ROPNnCmicmoVMpgJw8LKjFGJxWBVZp/t9axLWKZRaBFVLMCFx96tPR53/a9nA6lJ5COdI/i5BJplS/nsgso/cmmJQAAAABJRU5ErkJggg==')
						no-repeat
						left center;
					}
					.deco_button.debranding {
						/*background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAAUCAMAAABYi/ZGAAAAvVBMVEUAAAA8KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG48KG7///81IWk5JWwqE2AgCVkyHWdsXZJoWY9VRIE/LHE+K3AeB1j49/p/cqB7bp1MOXpEMHQ9KW8uGWQjDFwNAEvy8PXc2OXRzN2so8GkmruakLSXjLKIfKd0ZphwYZVQPn1INXdALXEYAVPy8fbs6vHj4OrAudC3r8mQhKyKfqhcTIaS87wZAAAAE3RSTlMAv63yF/joutTS0cmnlWtjVzgmBI/BHgAAAMtJREFUGNNtz9dywkAMBdC1E0J6Anu1u+64gE3vvfz/Z+GlDGBzH6SZM6ORxP555eU+Ff7HqrwYkxm6eRTQvGA0G8X0YD5lLuxUKvKv5rWkG8LJpmOrJZXf1EbWBkDsAB2ydyS0qQiHCM66a2Pfa1ikLRgiExh32t3VQAnRPNkI5GDS7lsDlxLBtcl4CWA6RAPJ9jLryVk/sudp2HPTSXjekR+zUErwYCGT6y3lf80nVje+jFu+zffcHvP7yd9eC1YtE/vgJWI/tbwcAZnyGufX95SfAAAAAElFTkSuQmCC')*/
						background: url('data:image/png;base64,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/GMzLdsAADsiaVRYdFhNTDpjb20uYWRvYmUueG1wAAAAAAA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/Pgo8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA1LjYtYzA2NyA3OS4xNTc3NDcsIDIwMTUvMDMvMzAtMjM6NDA6NDIgICAgICAgICI+CiAgIDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+CiAgICAgIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiCiAgICAgICAgICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgICAgICAgICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICAgICAgICAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgICAgICAgICB4bWxuczpzdEV2dD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlRXZlbnQjIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgICAgICAgICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iPgogICAgICAgICA8eG1wOkNyZWF0b3JUb29sPkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE1IChNYWNpbnRvc2gpPC94bXA6Q3JlYXRvclRvb2w+CiAgICAgICAgIDx4bXA6Q3JlYXRlRGF0ZT4yMDE2LTA2LTMwVDE3OjQ4OjM4KzAzOjAwPC94bXA6Q3JlYXRlRGF0ZT4KICAgICAgICAgPHhtcDpNb2RpZnlEYXRlPjIwMTYtMDYtMzBUMTc6NDk6MzQrMDM6MDA8L3htcDpNb2RpZnlEYXRlPgogICAgICAgICA8eG1wOk1ldGFkYXRhRGF0ZT4yMDE2LTA2LTMwVDE3OjQ5OjM0KzAzOjAwPC94bXA6TWV0YWRhdGFEYXRlPgogICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3BuZzwvZGM6Zm9ybWF0PgogICAgICAgICA8cGhvdG9zaG9wOkNvbG9yTW9kZT4zPC9waG90b3Nob3A6Q29sb3JNb2RlPgogICAgICAgICA8cGhvdG9zaG9wOklDQ1Byb2ZpbGU+c1JHQiBJRUM2MTk2Ni0yLjE8L3Bob3Rvc2hvcDpJQ0NQcm9maWxlPgogICAgICAgICA8eG1wTU06SW5zdGFuY2VJRD54bXAuaWlkOmNiN2VlZjY4LWZjNzAtNDgzYi1iMjNmLWRhMDUyMTQ0Yzc3ZTwveG1wTU06SW5zdGFuY2VJRD4KICAgICAgICAgPHhtcE1NOkRvY3VtZW50SUQ+YWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjQxNGZjNzU2LTdmNjAtMTE3OS05Y2IwLTlmOWNkMzM2ZDFhNTwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOmE2Yzc2MDE1LWU0N2EtNDFlOS05NWQ0LTJmNzg1YTM3ZjExMjwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDphNmM3NjAxNS1lNDdhLTQxZTktOTVkNC0yZjc4NWEzN2YxMTI8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTYtMDYtMzBUMTc6NDg6MzgrMDM6MDA8L3N0RXZ0OndoZW4+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpzb2Z0d2FyZUFnZW50PkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE1IChNYWNpbnRvc2gpPC9zdEV2dDpzb2Z0d2FyZUFnZW50PgogICAgICAgICAgICAgICA8L3JkZjpsaT4KICAgICAgICAgICAgICAgPHJkZjpsaSByZGY6cGFyc2VUeXBlPSJSZXNvdXJjZSI+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDphY3Rpb24+Y29udmVydGVkPC9zdEV2dDphY3Rpb24+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpwYXJhbWV0ZXJzPmZyb20gYXBwbGljYXRpb24vdm5kLmFkb2JlLnBob3Rvc2hvcCB0byBpbWFnZS9wbmc8L3N0RXZ0OnBhcmFtZXRlcnM+CiAgICAgICAgICAgICAgIDwvcmRmOmxpPgogICAgICAgICAgICAgICA8cmRmOmxpIHJkZjpwYXJzZVR5cGU9IlJlc291cmNlIj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OmFjdGlvbj5zYXZlZDwvc3RFdnQ6YWN0aW9uPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6aW5zdGFuY2VJRD54bXAuaWlkOmNiN2VlZjY4LWZjNzAtNDgzYi1iMjNmLWRhMDUyMTQ0Yzc3ZTwvc3RFdnQ6aW5zdGFuY2VJRD4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OndoZW4+MjAxNi0wNi0zMFQxNzo0OTozNCswMzowMDwvc3RFdnQ6d2hlbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OnNvZnR3YXJlQWdlbnQ+QWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCk8L3N0RXZ0OnNvZnR3YXJlQWdlbnQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpjaGFuZ2VkPi88L3N0RXZ0OmNoYW5nZWQ+CiAgICAgICAgICAgICAgIDwvcmRmOmxpPgogICAgICAgICAgICA8L3JkZjpTZXE+CiAgICAgICAgIDwveG1wTU06SGlzdG9yeT4KICAgICAgICAgPHRpZmY6T3JpZW50YXRpb24+MTwvdGlmZjpPcmllbnRhdGlvbj4KICAgICAgICAgPHRpZmY6WFJlc29sdXRpb24+NzIwMDAwLzEwMDAwPC90aWZmOlhSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpZUmVzb2x1dGlvbj43MjAwMDAvMTAwMDA8L3RpZmY6WVJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOlJlc29sdXRpb25Vbml0PjI8L3RpZmY6UmVzb2x1dGlvblVuaXQ+CiAgICAgICAgIDxleGlmOkNvbG9yU3BhY2U+MTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MjA8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MjA8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAKPD94cGFja2V0IGVuZD0idyI/PiTWf0EAAAAgY0hSTQAAeiUAAICDAAD5/wAAgOkAAHUwAADqYAAAOpgAABdvkl/FRgAAAzBJREFUeNqMlVtoVUcUhr+ZfTs5iYraYBKbeNJUPBrx2kTTpBd7s+ahUTDxJa1IrS++NvZBEDkWvPShghQE7UOlUIqUvkRRq6R4Q7GgxZRqSbwkaEzSBDHWnLP3nj19yGxJTNT8MAysWetn1qy1/hF16QwTwAFCQAPfAD8b+wagBRCADQTPBtpMjNUm8BLwJbDR2AuBCFgJ7AWOv4zwfaAc+Ah426yYKMY2s/cCJcBt4Ex8KJ8hfBM4BDTycjQa35UT3fB1oBn4ID4QAnxfEQaKvKT7IuI15s1/BDriG/rADqA29vJ9xbwFxXzcsBitQevnEtaaWD9OuQBYaN4EgDBQPBz4j5p35tKSqcd2JCpUZIcDctkAPZ6913AU2EAVcGwkT/CzIYVFU5lXWcyUaQmGn/ioMEJaguVV5fi5kL+v30MIQXY4wPNsHNcq0ppjwHs20GbKX5/LhqQXFrPnuw0kCzyS+S5dtwZI5Dls393AshUplIpo/eUqh/b/zqatNVw+30nHjV4cxzoOtElgc1ypMFCsb65GSkHThwc4e+oGQgreqHmNd1fP54eD5/ittZ1Pt9SxpKqM5i21LFg0m8BXGI7NY/pQSsGUqXnc7ujnZnsP7de6qUjPwkvYRJFmzdrFhIHi5l89/Ns3xNq3vkVrSOa7Y9rmMLAOqNdao1RE0ezpvDpnBpVLStFaI8RIG2VafsXzbOZUFNJ9Z5DS1EwG+od4PJRDSnEJOGyVvbJqFfA1QKgiokjT0LSMxo3VpCtLGBx4zJGD51lanWL9Z9V80rQcaQn+/KOLn05spff+I65duYvr2nOBs6IunSkwI/Y9UBT4ivmLSihNzeRe1yBCCK5f7Wb6jHxW1FWQzYZcudBJqCKqasrp/KePvgePeixLfg6cE0ZtyoA7RkXIZUOUUljWSN97CQcVRuRyASBI5DlIKRh+4uO6NpYjNZoU0BUXxQUyZvRqvYQ9TjcsW5K0vTE2M5IX0Jw2HE+jOoCdgBo9fpPESWDX89TmIvAFcHQSREeN78UX6WGsaw+AWUZgtwH9o3Rxn2niI0DrZBX7pJlvbbIY/QV8NeoLGIf/BwD5vA9LCDvEWwAAAABJRU5ErkJggg==')
						no-repeat
						left center;
					}
					.deco_button.deadblocker {
						background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAPFBMVEUAAABGqh5Gqh5Gqh5Gqh5Gqh5Gqh5Gqh7R6sej1Y////+MynOXz4HG5bn0+vLp9eTd79ZSryy636t0v1bwgAlwAAAAB3RSTlMA0MDwoBAwnUp3lgAAAHlJREFUGNNt0dsKwCAIANDcVbPVLv//r7NcGEwfRA4JpqHGCjC1AFgbNFt6uYh22+a9xbypmpmajWpmGiCoEXUVmoLWiP2tkGKigpJ4xBQxYk08IJ3Sjhkj/bDQ/QzItZ3z8bXrSBcfxJKSjuQO73/T1Mxbnb9k9xwvXLIH3Gnfdh0AAAAASUVORK5CYII=')
						no-repeat
						left center;
					}
					.deco-right-sidebar-widget .inside {
						padding-left: 2px;
					}
					.deco-right-sidebar-widget ul {
						list-style-type: disc;
						padding-left: 15px;
					}
					#mistape_custom_caption_text{
						width: 100%;
						max-width: 600px;
					}
					@media screen and (max-width: 840px) {
						#mistape-sidebar{
							position: relative;
							right: initial;
							top: initial;
							margin-top: 20px;
						}
						.deco-right-sidebar-widget{
							width: calc(100% - 30px);
						}
						#mistape-configuration form table{
							width: 100%;
						}
					}
				</style>
			</form>

		</div>
		<?php
		printf( '<div id="mistape-help" class="mistape-tab-contents" %s>',
			$active_tab == 'help' ? '' : 'style="display: none;" ' );
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
		add_settings_field( 'mistape_email_recipient', __( 'Email recipient', 'mistape' ),
			array( $this, 'field_email_recipient' ), 'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_post_types', __( 'Post types', 'mistape' ), array( $this, 'field_post_types' ),
			'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_register_shortcode', __( 'Shortcodes', 'mistape' ),
			array( $this, 'field_register_shortcode' ), 'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_caption_format', __( 'Caption format', 'mistape' ),
			array( $this, 'field_caption_format' ), 'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_caption_text_mode', __( 'Caption text mode', 'mistape' ),
			array( $this, 'field_caption_text_mode' ), 'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_show_logo_in_caption', __( 'Mistape Logo', 'mistape' ),
			array( $this, 'field_show_logo_in_caption' ), 'mistape_options', 'mistape_configuration' );
		add_settings_field( 'mistape_dialog_mode', __( 'Dialog mode', 'mistape' ), array( $this, 'field_dialog_mode' ),
			'mistape_options', 'mistape_configuration' );

		if ( is_multisite() && is_main_site() ) {
			add_settings_field( 'mistape_multisite_inheritance', __( 'Multisite inheritance', 'mistape' ),
				array( $this, 'field_multisite_inheritance' ), 'mistape_options', 'mistape_configuration' );
		}
	}

	/**
	 * Section callback
	 */
	public function section_configuration() {
	}

	/**
	 * Email recipient selection
	 */
	public function field_email_recipient() {
		echo '
		<fieldset>';


		foreach ( $this->email_recipient_types as $value => $label ) {
			echo '
				<label><input id="mistape_email_recipient_type-' . $value . '" type="radio"
				  name="mistape_options[email_recipient][type]" value="' . esc_attr( $value ) . '" ' .
			     checked( $value, $this->options['email_recipient']['type'], false ) . ' />' . esc_html( $label ) . '
				</label><br>';
		}

		echo '
			<div id="mistape_email_recipient_list-admin"' . ( $this->options['email_recipient']['type'] == 'admin' ? '' : 'style="display: none;"' ) . '>';

		echo '
			<select name="mistape_options[email_recipient][id][admin]">';

		$admins = $this->get_user_list_by_role( 'administrator' );
		foreach ( $admins as $user ) {
			echo '
				<option value="' . $user->ID . '" ' . selected( $user->ID, $this->options['email_recipient']['id'],
					false ) . '>' . esc_html( $user->user_nicename . ' (' . $user->user_email . ')' ) . '</option>';
		}

		echo '
			</select>
			</div>';

		echo '
			<div id="mistape_email_recipient_list-editor"' . ( $this->options['email_recipient']['type'] == 'editor' ? '' : 'style="display: none;"' ) . '>';


		$editors = $this->get_user_list_by_role( 'editor' );
		if ( ! empty( $editors ) ) {
			echo '<select name="mistape_options[email_recipient][id][editor]">';
			foreach ( $editors as $user ) {
				echo '
				<option value="' . $user->ID . '" ' . selected( $user->ID, $this->options['email_recipient']['id'],
						false ) . '>' . esc_html( $user->user_nicename . ' (' . $user->user_email . ')' ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<select><option value="">-- ' . _x( 'no editors found', 'select option, shown when no users with editor role are present', 'mistape' ) . ' --</option></select>';
		}

		echo '
			</div>
			<div id="mistape_email_recipient_list-other" ' . ( $this->options['email_recipient']['type'] == 'other' ? '' : 'style="display: none;"' ) . '>
				<input type="text" class="regular-text" name="mistape_options[email_recipient][email]" value="' . esc_attr( $this->options['email_recipient']['email'] ) . '" />
				<p class="description">' . __( 'separate multiple recipients with commas', 'mistape' ) . '</p>
			</div>
			<br>
			<label><input id="mistape_email_recipient-post_author_first" type="checkbox" name="mistape_options[email_recipient][post_author_first]" value="1" ' . checked( 'yes',
				$this->options['email_recipient']['post_author_first'],
				false ) . '/>' . __( 'If post ID is determined, notify post author instead', 'mistape' ) . '</label>
		</fieldset>';
	}

	/**
	 * Post types to show caption in
	 */
	public function field_post_types() {
		echo '
		<fieldset style="max-width: 600px;">';

		foreach ( $this->post_types as $value => $label ) {
			echo '
			<label style="padding-right: 8px; min-width: 60px;"><input id="mistape_post_type-' . $value . '" type="checkbox" name="mistape_options[post_types][' . $value . ']" value="1" ' . checked( true,
					in_array( $value, $this->options['post_types'] ),
					false ) . ' />' . esc_html( $label ) . '</label>	';
		}

		echo '
			<p class="description">' . __( '"Press Ctrl+Enter&hellip;" captions will be displayed at the bottom of selected post types.',
				'mistape' ) . '</p>
		</fieldset>';
	}

	/**
	 * Shortcode option
	 */
	public function field_register_shortcode() {
		echo '
		<fieldset>
			<label><input id="mistape_register_shortcode" type="checkbox" name="mistape_options[register_shortcode]" value="1" ' . checked( 'yes',
				$this->options['register_shortcode'], false ) . '/>' . __( 'Register <code>[mistape]</code> shortcode.',
				'mistape' ) . '</label>
			<p class="description">' . __( 'Enable if manual caption insertion via shortcodes is needed.', 'mistape' ) . '</p>
			<p class="description">' . __( 'Usage examples are in Help section.', 'mistape' ) . '</p>
			<p class="description">' . __( 'When enabled, Mistape Ctrl+Enter listener works on all pages, not only on enabled post types.', 'mistape' ) . '</p>
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
			<label><input id="mistape_caption_format-' . $value . '" type="radio" name="mistape_options[caption_format]" value="' . esc_attr( $value ) . '" ' . checked( $value,
					$this->options['caption_format'], false ) . ' />' . esc_html( $label ) . '</label><br>';
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
			     'value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['caption_text_mode'],
					false ) . ' />' . $label['name'];
			echo empty( $label['description'] ) ? ':' : ' <span class="description">(' . $label['description'] . ')</span>';
			echo '</label><br>';
		}

		$textarea_contents = $this->get_caption_text();
		$textarea_state    = $this->options['caption_text_mode'] == 'default' ? ' disabled="disabled"' : '';

		echo '<textarea id="mistape_custom_caption_text" name="mistape_options[custom_caption_text]" cols="70" rows="4"
			data-default="' . esc_attr( $this->get_default_caption_text() ) . '"' . $textarea_state . ' />' . esc_textarea( $textarea_contents ) . '</textarea><br>
		</fieldset>';
	}

	/**
	 * Show Mistape logo in caption
	 */
	public function field_show_logo_in_caption() {
		echo '
		<fieldset>
			<label><input id="mistape_show_logo_in_captione" type="checkbox" name="mistape_options[show_logo_in_caption]" value="1" ' . checked( 'yes',
				$this->options['show_logo_in_caption'], false ) . '/>' . __( 'Caption with Mistape logo', 'mistape' ) . '</label>
		</fieldset>';
	}

	/**
	 * Dialog mode: ask for a comment or fire notification straight off
	 */
	public function field_dialog_mode() {
		echo '
		<fieldset>';

		foreach ( $this->dialog_modes as $value => $label ) {
			echo '
			<label><input class="dialog_mode_choice" id="mistape_caption_format-' . $value .
			     '" type="radio" name="mistape_options[dialog_mode]" value="' . esc_attr( $value ) . '" ' .
			     checked( $value, $this->options['dialog_mode'], false ) . ' />' . esc_html( $label ) .
			     '</label><br>';
		}
		echo '<button class="button" id="preview-dialog-btn">' . __( 'Preview dialog', 'mistape' ) . '</button>';
		echo '<span id="preview-dialog-spinner" class="spinner"></span>';
	}

	/**
	 * Multisite inheritance: copy settings from main site to newly created blogs
	 */
	public function field_multisite_inheritance() {
		echo '
		<fieldset>
			<label><input id="mistape_multisite_inheritance" type="checkbox" name="mistape_options[multisite_inheritance]" value="1" ' .
		     checked( 'yes', $this->options['multisite_inheritance'],
			     false ) . '/>' . __( 'Copy settings from main site when new blog is created', 'mistape' ) . '
	        </label>
		</fieldset>';
	}

	/**
	 * Validate options
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_options( $input ) {
		$this->init();

		if ( ! current_user_can( 'manage_options' ) ) {
			return $input;
		}

		if ( isset( $_POST['option_page'] ) && $_POST['option_page'] == 'mistape_options' ) {

			// mail recipient
			$input['email_recipient']['type']              = sanitize_text_field( isset( $input['email_recipient']['type'] ) && in_array( $input['email_recipient']['type'],
				array_keys( $this->email_recipient_types ) ) ? $input['email_recipient']['type'] : self::$defaults['email_recipient']['type'] );
			$input['email_recipient']['post_author_first'] = $input['email_recipient']['post_author_first'] === '1' ? 'yes' : 'no';

			if ( $input['email_recipient']['type'] == 'admin' && isset( $input['email_recipient']['id']['admin'] ) && ( user_can( $input['email_recipient']['id']['admin'],
					'administrator' ) )
			) {
				$input['email_recipient']['id'] = $input['email_recipient']['id']['admin'];
			} elseif ( $input['email_recipient']['type'] == 'editor' && isset( $input['email_recipient']['id']['editor'] ) && ( user_can( $input['email_recipient']['id']['editor'],
					'editor' ) )
			) {
				$input['email_recipient']['id'] = $input['email_recipient']['id']['editor'];
			} elseif ( $input['email_recipient']['type'] == 'other' && isset( $input['email_recipient']['email'] ) ) {
				$input['email_recipient']['id'] = '0';
				$emails                         = explode( ',',
					str_replace( array( ', ', ' ' ), ',', $input['email_recipient']['email'] ) );
				$invalid_emails                 = array();
				foreach ( $emails as $key => &$email ) {
					if ( ! is_email( $email ) ) {
						$invalid_emails[] = $email;
						unset( $emails[ $key ] );
					}
					$email = sanitize_email( $email );
				}
				if ( $invalid_emails ) {
					add_settings_error(
						'mistape_options',
						esc_attr( 'invalid_recipient' ),
						sprintf( __( 'ERROR: You entered invalid email address: %s', 'mistape' ),
							trim( implode( ',', $invalid_emails ), "," ) ),
						'error'
					);
				}

				$input['email_recipient']['email'] = trim( implode( ',', $emails ), "," );
			} else {
				add_settings_error(
					'mistape_options',
					esc_attr( 'invalid_recipient' ),
					__( 'ERROR: You didn\'t select valid email recipient.', 'mistape' ),
					'error'
				);
				$input['email_recipient']['id'] = '1';
				$input['email_recipient']       = $this->options['email_recipient'];
			}

			// post types
			$input['post_types'] = isset( $input['post_types'] ) && is_array( $input['post_types'] ) && count( array_intersect( array_keys( $input['post_types'] ),
				array_keys( $this->post_types ) ) ) === count( $input['post_types'] ) ? array_keys( $input['post_types'] ) : array();

			// shortcode option
			$input['register_shortcode'] = (bool) isset( $input['register_shortcode'] ) ? 'yes' : 'no';

			// caption type
			$input['caption_format'] = isset( $input['caption_format'] ) && in_array( $input['caption_format'],
				array_keys( $this->caption_formats ) ) ? $input['caption_format'] : self::$defaults['caption_format'];
			if ( $input['caption_format'] === 'image' ) {
				if ( ! empty( $input['caption_image_url'] ) ) {
					$input['caption_image_url'] = esc_url( $input['caption_image_url'] );
				} else {
					add_settings_error(
						'mistape_options',
						esc_attr( 'no_image_url' ),
						__( 'ERROR: You didn\'t enter caption image URL.', 'mistape' ),
						'error'
					);
					$input['caption_format']    = self::$defaults['caption_format'];
					$input['caption_image_url'] = self::$defaults['caption_image_url'];
				}
			};

			// caption text mode
			$input['caption_text_mode']   = isset( $input['caption_text_mode'] ) && in_array( $input['caption_text_mode'],
				array_keys( $this->caption_text_modes ) ) ? $input['caption_text_mode'] : self::$defaults['caption_text_mode'];
			$input['custom_caption_text'] = $input['caption_text_mode'] == 'custom' && $input['custom_caption_text'] !== $this->default_caption_text ? wp_kses_post( $input['custom_caption_text'] ) : '';

			$input['show_logo_in_caption'] = $input['show_logo_in_caption'] === '1' ? 'yes' : 'no';

			$input['multisite_inheritance'] = isset( $input['multisite_inheritance'] ) && $input['multisite_inheritance'] === '1' ? 'yes' : 'no';

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
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$plugin = plugin_basename( self::$plugin_path );

		if ( $file == $plugin ) {
			array_unshift( $links,
				sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=mistape_settings' ),
					__( 'Settings', 'mistape' ) ) );
		}

		return $links;
	}

	/**
	 * Add initial options
	 *
	 * @param null $blog_id
	 */
	public function activation( $blog_id = null ) {
		$blog_id = (int) $blog_id;

		if ( empty( $blog_id ) ) {
			$blog_id = get_current_blog_id();
		}

		if ( get_current_blog_id() == $blog_id ) {
			add_option( 'mistape_options', $this->options, '', 'yes' );
			add_option( 'mistape_version', self::$version, '', 'no' );
		} else {
			switch_to_blog( $blog_id );
			add_option( 'mistape_options', $this->options, '', 'yes' );
			add_option( 'mistape_version', self::$version, '', 'no' );
			restore_current_blog();
		}

		if ( ! empty( $this->options['addons_to_activate'] ) ) {
			activate_plugins( $this->options['addons_to_activate'] );
			unset( $this->options['addons_to_activate'] );
			update_option( 'mistape_options', $this->options );
		}

		self::create_db();
	}

	public function deactivate_addons( $value ) {

		remove_filter( 'option_active_plugins', array( $this, 'deactivate_addons' ) );

		foreach ( static::$supported_addons as $addon ) {
			$plugin = $addon . '/' . $addon . '.php';
			if ( ( $key = array_search( $plugin, $value ) ) !== false ) {
				deactivate_plugins( $plugin, true );
				unset( $value[ $key ] );
				$deactivated[] = $plugin;
			}
		}

		if ( ! empty( $deactivated ) ) {
			$options                       = self::get_options();
			$options['addons_to_activate'] = $deactivated;
			update_option( 'mistape_options', $options );
		}

		return $value;
	}

	/**
	 * Delete settings on plugin uninstall
	 */
	public static function uninstall_cleanup() {
		global $wpdb;

		$table_name = "mistape_reports";
		$sql        = "DROP TABLE IF EXISTS $table_name;";
		$wpdb->query( $sql );

		delete_option( 'mistape_options' );
		delete_option( 'mistape_version' );
	}

	/**
	 * Load scripts and styles - admin
	 *
	 * @param $page
	 */
	public function admin_load_scripts_styles( $page ) {
		if ( strpos( $page, '_page_mistape_settings', true ) === false ) {
			return;
		}

		$this->enqueue_dialog_assets();

		// admin page script
		wp_enqueue_script( 'mistape-admin', plugins_url( 'assets/js/admin.js', self::$plugin_path ), array( 'mistape-front' ), self::$version, true );
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
				$html .= __( '<strong>Mistape</strong> settings notice will be dismissed after saving changes.',
					'mistape' );
			} else {
				$html .= sprintf( __( '<strong>Mistape</strong> must now be <a href="%s">configured</a> before use.',
					'mistape' ), admin_url( 'options-general.php?page=mistape_settings' ) );
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
			'role'    => $role,
			'fields'  => array(
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
	public function get_post_types_list() {
		$post_types = get_post_types(
			array( 'public' => true ),
			'objects'
		);

		$post_types_list = array();
		foreach ( $post_types as $id => $post_type ) {
			$post_types_list[ $id ] = $post_type->label;
		}

		return $post_types_list;
	}

	/**
	 * Echo Help tab contents
	 */
	private static function print_help_page() {
		?>
		<div class="card">
			<h3><?php _e( 'Shortcodes', 'mistape' ) ?></h3>
			<h4><?php _e( 'Optional shortcode parameters are:', 'mistape' ) ?></h4>
			<ul>
				<li><code>'format', </code> — <?php _e( "can be 'text' or 'image'", 'mistape' ) ?></li>
				<li><code>'class', </code> — <?php _e( 'override default css class', 'mistape' ) ?></li>
				<li><code>'text', </code> — <?php _e( 'override caption text', 'mistape' ) ?></li>
				<li><code>'image', </code> — <?php _e( 'override image URL', 'mistape' ) ?></li>
			</ul>
			<p><?php _e( 'When no parameters specified, general configuration is used.', 'mistape' ) ?><br>
				<?php _e( 'If image url is specified, format parameter can be omitted.', 'mistape' ) ?></p>
			<h4><?php _e( 'Shortcode usage example:', 'mistape' ) ?></h4>
			<ul>
				<li><p><code>[mistape format="text" class="mistape_caption_sidebar"]</code></p></li>
			</ul>
			<h4><?php _e( 'PHP code example:', 'mistape' ) ?></h4>
			<ul>
				<li><p><code>&lt;?php do_shortcode( '[mistape format="image" class="mistape_caption_footer"
							image="/wp-admin/images/yes.png"]' ); ?&gt;</code></p></li>
			</ul>
		</div>

		<div class="card">
			<h3><?php _e( 'Hooks', 'mistape' ) ?></h3>

			<ul>

				<li class="mistape-hook-block">
					<code>'mistape_caption_text', <span class="mistape-var-str">$text</span></code>
					<p class="description"><?php _e( 'Allows to modify caption text globally (preferred over HTML filter).',
							'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_caption_output', <span class="mistape-var-str">$html</span>, <span
							class="mistape-var-arr">$options</span></code></code>
					<p class="description"><?php _e( 'Allows to modify the caption HTML before output.',
							'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_dialog_args', <span class="mistape-var-arr">$args</span></code>
					<p class="description"><?php _e( 'Allows to modify modal dialog strings (preferred over HTML filter).',
							'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_dialog_output', <span class="mistape-var-str">$html</span>, <span
							class="mistape-var-arr">$options</span></code></code>
					<p class="description"><?php _e( 'Allows to modify the modal dialog HTML before output.',
							'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_custom_email_handling', <span class="mistape-var-bool">$stop</span>, <span class="mistape-var-obj">$mistape_object</span></code>
					<p class="description"><?php _e( 'Allows to override email sending logic.', 'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_mail_recipient', <span class="mistape-var-str">$recipient</span>, <span
							class="mistape-var-str">$url</span>, <span class="mistape-var-obj">$user</span></code>
					<p class="description"><?php _e( 'Allows to change email recipient.', 'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_mail_subject', <span class="mistape-var-str">$subject</span>, <span
							class="mistape-var-str">$referrer</span>, <span class="mistape-var-obj">$user</span></code>
					<p class="description"><?php _e( 'Allows to change email subject.', 'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_mail_message', <span class="mistape-var-str">$message</span>, <span
							class="mistape-var-str">$referrer</span>, <span class="mistape-var-obj">$user</span></code>
					<p class="description"><?php _e( 'Allows to modify email message to send.', 'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_custom_email_handling', <span class="mistape-var-bool">$stop</span>, <span
							class="mistape-var-obj">$ajax_obj</span></code>
					<p class="description"><?php _e( 'Allows for custom reports handling. Refer to code for implementation details.',
							'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_options', <span class="mistape-var-arr">$options</span></code>
					<p class="description"><?php _e( 'Allows to modify global options array during initialization.',
							'mistape' ) ?></p>
				</li>

				<li class="mistape-hook-block">
					<code>'mistape_is_appropriate_post', <span class="mistape-var-bool">$result</span></code>
					<p class="description"><?php _e( 'Allows to add custom logic for whether to output Mistape to front end or not.',
							'mistape' ) ?></p>
				</li>

			</ul>

		</div>
		<style scoped>
			[class^="mistape-var"] {
				color: #9876AA;
				padding: 2px;
				font-style: normal;
			}

			[class^="mistape-var-"]:before {
				font-style: italic;
				color: #aaa;
			}

			.mistape-var-arr:before {
				content: "arr ";
			}

			.mistape-var-bool:before {
				content: "bool ";
			}

			.mistape-var-obj:before {
				content: "obj ";
			}

			.mistape-var-str:before {
				content: "str ";
			}

			.mistape-hook-block {
				margin-bottom: 20px;
			}

			.mistape-hook-block > p.description {
				margin-left: 6px;
			}

			#mistape-configuration .spinner {
				float: none;
			}
		</style>
		<?php
	}

	public function insert_dialog() {
		$args = array(
			'reported_text_preview' => 'Lorem <span class="mistape_mistake_highlight">upsum</span> dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
		);
		echo $this->get_dialog_html( $args );
	}
}