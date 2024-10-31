<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

use PersonalizeWP\DB_Manager;
use PersonalizeWP\Admin\Admin_Settings;
use PersonalizeWP\Admin\REST\Settings_Controller;
use PersonalizeWP\Admin\Onboarding_Wizard;
use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */
class Admin {

	use SingletonTrait;

	/**
	 * Holds Instance of plugin object
	 *
	 * @var PersonalizeWP
	 */
	private $plugin;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string  $personalizewp The ID of this plugin.
	 */
	private $personalizewp;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string  $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Stores each of the admin screens
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $screens = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Switch to single plugin param
	 * @since 2.5.0 Removed param, use singletontrait
	 */
	protected function __construct() {
		$this->plugin        = \personalizewp();
		$this->personalizewp = $this->plugin->get_personalizewp();
		$this->version       = $this->plugin->get_version();

		$this->setup();
	}

	/**
	 * Execute admin hooks
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function setup() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action( 'admin_menu', [ $this, 'create_admin_menus' ] );
		add_filter( 'parent_file', [ $this, 'set_active_admin_menu' ] );

		// Restrict database changes to only occur within the admin.
		add_action( 'admin_init', [ $this, 'migrate_database' ] );

		// Restrict admin processing to running only in the admin.
		add_action( 'admin_init', [ $this, 'process' ] );

		// Setup all screens
		$this->screens = array(
			PERSONALIZEWP_ADMIN_SETTINGS_SLUG => Admin_Settings::instance(),
		);

		add_action( 'admin_init', [ $this, 'setup_onboarding_wizard' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin->locations['file'] ), [ $this, 'add_plugin_action_link' ], 10, 1 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
		// Hide all unrelated to the plugin notices on the plugin admin pages.
		add_action( 'admin_print_scripts', [ $this, 'hide_unrelated_notices' ] );

		// Allow dismissing the first time (legacy onboarding) messaging on various screens.
		add_action( 'wp_ajax_pwp_dismiss_onboarding_message', [ $this, 'dismiss_onboarding_message' ] );

		// Add additional query args to WP defaults that we use to display messaging.
		add_filter(
			'removable_query_args',
			function ( $args ) {
				$args[] = 'created';
				$args[] = 'duplicated';
				$args[] = 'duplicate_error';
				$args[] = 'activated';
				$args[] = 'deactivated';
				$args[] = 'onboarding_complete';
				return $args;
			}
		);

		add_action( 'rest_api_init', [ $this, 'create_admin_routes' ] );

		add_action( 'admin_print_footer_scripts', [ '\PersonalizeWP\Admin\Utils', 'output_inline_svgs' ], 100 );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->version;

		if ( $this->is_admin_page() ) {
			wp_enqueue_style( $this->personalizewp . '_jquery_ui', plugin_dir_url( __FILE__ ) . 'libs/css/jquery-ui.css', array(), '1.5', 'all' ); // For datepicker etc

			wp_enqueue_style( $this->personalizewp, plugin_dir_url( __FILE__ ) . "css/admin{$suffix}.css", array(), $version, 'all' );

			// Ensures focused CSS styles
			add_filter( 'admin_body_class', [ $this, 'admin_body_class' ] );
		}

		if ( $this->is_block_editing_page() ) {
			wp_enqueue_style( $this->personalizewp, plugin_dir_url( __FILE__ ) . "css/admin{$suffix}.css", array(), $version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( $this->is_admin_page() ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );

			$properties = require plugin_dir_path( __FILE__ ) . 'js/admin.asset.php';
			$js_version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $properties['version'] : $this->version;
			wp_enqueue_script( $this->personalizewp, plugin_dir_url( __FILE__ ) . 'js/admin.js', $properties['dependencies'], $js_version, false );

			// Various AJAX callbacks etc
			wp_localize_script(
				$this->personalizewp,
				'pwpSettings',
				[
					'kb'    => 'https://personalizewp.com/knowledge-base',
					'url'   => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'pwp-ajax-nonce' ),
				]
			);
		} else {
			// Fake register to allow variable below.
			wp_register_script( $this->personalizewp, false, [], $this->version, true );
			wp_enqueue_script( $this->personalizewp );
			wp_add_inline_script(
				$this->personalizewp,
				'
					( function() {
						if ( window.pwpSettings ) {
							document.querySelector(`ul#adminmenu a[href=\'${window.pwpSettings.kb}\']`).setAttribute("target", "_blank");
						}
					}() );
				',
			);
			wp_localize_script(
				$this->personalizewp,
				'pwpSettings',
				[
					'kb' => 'https://personalizewp.com/knowledge-base',
				]
			);
		}
	}

	/**
	 * Add a PWP class to body element of wp-admin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes CSS classes for the body tag in the admin, a space separated string.
	 *
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		$classes .= ' pwp-page';
		return $classes;
	}

	/**
	 * Add plugin action links on Plugins page (lite version only).
	 *
	 * @since 1.2.0
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array
	 */
	public function add_plugin_action_link( $links ) {

		// Do not register lite plugin action links if on pro version.
		if ( $this->plugin->is_pro() ) {
			return $links;
		}

		$custom['personalizewp-pro'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer" style="color:#00a32a;font-weight:700;"
				onmouseover="this.style.color=\'#C127A0\';" onmouseout="this.style.color=\'#00a32a\';"
				>%3$s</a>',
			esc_url( 'https://personalizewp.com/pricing/?ref=all-plugins' ),
			esc_attr__( 'Upgrade to PersonalizeWP Pro', 'personalizewp' ),
			esc_html__( 'Get PersonalizeWP Pro', 'personalizewp' )
		);

		return array_merge( $custom, (array) $links );
	}

	/**
	 * Enqueue block panel script
	 */
	public function enqueue_block_editor_scripts() {

		/**
		 * Personalisation not currently supported within legacy widgets.
		 *
		 * @since 2.6.0 Expanded to include Site Editor
		 * @since 2.0.0
		 */
		if ( wp_script_is( 'wp-edit-widgets' ) ) {
			return;
		}

		$properties = require plugin_dir_path( __FILE__ ) . 'js/block-editor.asset.php';
		$js_version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $properties['version'] : $this->version;

		wp_enqueue_script( 'personalizewp-block-editor', plugin_dir_url( __FILE__ ) . 'js/block-editor.js', $properties['dependencies'], $js_version, false );
		// Add translations for the PWP block in the editor.
		wp_set_script_translations( 'personalizewp-block-editor', 'personalizewp', plugin_dir_path( __FILE__ ) . '../languages' );
	}

	/**
	 * Dashboard page
	 *
	 * @return void
	 */
	public function dashboard_menu() {
		\PersonalizeWP_Admin_Dashboard_Page::instance()->route();
	}

	/**
	 * Personalization page
	 *
	 * @return void
	 */
	public function personalization_menu() {
		\PersonalizeWP_Admin_Rules_Page::instance()->route();
	}

	/**
	 * Knowledge base page
	 *
	 * @return void
	 */
	public function categories_menu() {
		\PersonalizeWP_Admin_Categories_Page::instance()->route();
	}

	/**
	 * Return a base64 encoded SVG of the PersonalizeWP menu logo.
	 * Can be used as a data URI to use the logo inline in CSS.
	 *
	 * @return string
	 */
	public function get_base64_menu_logo() {
		$base_logo = file_get_contents( __DIR__ . '/img/menu_icon_personalize.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- File is local, not remote

		$encoded_logo = base64_encode( $base_logo ); // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found,WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- The encoded version is used as data URI to use the logo in CSS.

		return 'data:image/svg+xml;base64,' . $encoded_logo;
	}

	/**
	 * Create menu items for PersonalizeWP
	 *
	 * @return void
	 */
	public function create_admin_menus() {
		$parent_slug = PERSONALIZEWP_ADMIN_SLUG;

		$hook_suffix = add_menu_page(
			esc_html_x( 'Personalize', 'plugin name', 'personalizewp' ),
			esc_html_x( 'Personalize', 'plugin name', 'personalizewp' ),
			'manage_options',
			$parent_slug,
			[ $this, 'dashboard_menu' ],
			$this->get_base64_menu_logo(),
			21
		);

		add_submenu_page(
			$parent_slug,
			esc_html__( 'Dashboard', 'personalizewp' ),
			esc_html__( 'Dashboard', 'personalizewp' ),
			'manage_options',
			$parent_slug,
			[ $this, 'dashboard_menu' ]
		);

		/**
		 * Fires immediately after the main Personalize menu and it's dashboard sub-menu, and before any other sub-menus.
		 *
		 * @since 1.0.0
		 *
		 * @param string $parent_slug Slug of the main Personalize menu item
		 */
		do_action( 'personalizewp_menus_before', $parent_slug );

		add_submenu_page(
			$parent_slug,
			esc_html__( 'Personalization', 'personalizewp' ),
			esc_html__( 'Personalization', 'personalizewp' ),
			'manage_options',
			PERSONALIZEWP_ADMIN_RULES_SLUG,
			[ $this, 'personalization_menu' ]
		);

		// Not directly shown in the main admin menu, but required for permission checks
		add_submenu_page(
			PERSONALIZEWP_ADMIN_RULES_SLUG, // Use a different parent slug so it's not visible
			esc_html__( 'Categories', 'personalizewp' ),
			esc_html__( 'Categories', 'personalizewp' ),
			'manage_options',
			PERSONALIZEWP_ADMIN_CATEGORIES_SLUG,
			[ $this, 'categories_menu' ]
		);

		/**
		 * Fires after most Personalize sub-menus are added to the menu bar, apart from Settings and KB.
		 *
		 * @since 1.0.0
		 *
		 * @param string $parent_slug Slug of the main Personalize menu item
		 */
		do_action( 'personalizewp_menus_after', $parent_slug );

		add_submenu_page(
			$parent_slug,
			esc_html__( 'Settings', 'personalizewp' ),
			esc_html__( 'Settings', 'personalizewp' ),
			'manage_options',
			PERSONALIZEWP_ADMIN_SETTINGS_SLUG,
			[ $this->screens[ PERSONALIZEWP_ADMIN_SETTINGS_SLUG ], 'display_screen' ]
		);

		// Directly add KB item to menu.
		$icon = ' <span class="dashicons dashicons-external" style="transform:translateY(-2px)"></span>'; // Previously was ' <span class="bi bi-box-arrow-up-right"></span>',
		add_submenu_page(
			$parent_slug,
			'<span style="white-space:nowrap">' . esc_html__( 'Knowledge Base', 'personalizewp' ) . $icon . '</span>',
			'<span style="white-space:nowrap">' . esc_html__( 'Knowledge Base', 'personalizewp' ) . $icon . '</span>',
			'edit_pages',
			'https://personalizewp.com/knowledge-base/'
		);
	}

	/**
	 * Modifies admin menu of PersonalizeWP
	 * Sets active page for sections which are invisible in the menu,
	 * as Personalization categories and rules are within the same menu item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent_file The parent file.
	 *
	 * @return string
	 */
	public function set_active_admin_menu( $parent_file ) {
		global $plugin_page;

		if ( PERSONALIZEWP_ADMIN_CATEGORIES_SLUG === $plugin_page ) {
			$plugin_page = PERSONALIZEWP_ADMIN_RULES_SLUG;
		}

		return $parent_file;
	}

	/**
	 * Call process function on page class (usually to process POST data)
	 *
	 * @return void
	 */
	public function process() {
		// Check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: Form verified within each process

		switch ( $page ) {
			case PERSONALIZEWP_ADMIN_CATEGORIES_SLUG:
				\PersonalizeWP_Admin_Categories_Page::instance()->process();
				break;
			case PERSONALIZEWP_ADMIN_RULES_SLUG:
				\PersonalizeWP_Admin_Rules_Page::instance()->process();
				break;
		}
	}

	/**
	 * Registers routes to support the admin
	 *
	 * @return void
	 */
	public function create_admin_routes() {

		// Settings, for block editor.
		$rest_settings = new Settings_Controller();
		$rest_settings->register_routes();
	}

	/**
	 * Run DB migrations
	 *
	 * @return void
	 */
	public function migrate_database() {
		// Check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		DB_Manager::instance()->migrate();
	}

	/**
	 * Setup the onboarding wizard
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function setup_onboarding_wizard() {

		if ( ! $this->should_enqueue_onboarding_wizard() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$onboarding = new Onboarding_Wizard();
		$onboarding->setup();
	}

	/**
	 * Should the onboarding wizard be queued up to show.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function should_enqueue_onboarding_wizard() {

		if ( ! $this->is_admin_page() &&
			! wp_doing_ajax() &&
			// Check for completion action
			( empty( $_REQUEST['action'] ) || 'pwp_onboarding_complete' !== sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) )
		) {
			return false;
		}

		if ( defined( 'PWP_DISPLAY_ONBOARDING_WIZARD' ) && PWP_DISPLAY_ONBOARDING_WIZARD ) {
			return true;
		}

		return (bool) get_option( 'pwp_pending_onboarding' );
	}

	/**
	 * Remove all non-PersonalizeWP plugin notices from our plugin pages.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function hide_unrelated_notices() {

		// Bail if we're not on our screen or page.
		if ( ! $this->is_admin_page() ) {
			return;
		}

		$this->remove_unrelated_actions( 'user_admin_notices' );
		$this->remove_unrelated_actions( 'admin_notices' );
		$this->remove_unrelated_actions( 'all_admin_notices' );
		$this->remove_unrelated_actions( 'network_admin_notices' );
	}

	/**
	 * Remove all non-PersonalizeWP notices from the our plugin pages based on the provided action hook.
	 *
	 * @since 2.0.0
	 *
	 * @param string $action The name of the action.
	 *
	 * @return void
	 */
	private function remove_unrelated_actions( $action ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
			return;
		}

		foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if (
					( // Cover object method callback case.
						is_array( $arr['function'] ) &&
						is_object( $arr['function'][0] ) &&
						str_starts_with( strtolower( get_class( $arr['function'][0] ) ), 'personalizewp' )
					) ||
					( // Cover class static method callback case.
						! empty( $name ) &&
						str_starts_with( strtolower( $name ), 'personalizewp' )
					)
				) {
					continue;
				}

				unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
			}
		}
	}

	/**
	 * Is the current page an admin page of PersonalizeWP?
	 *
	 * @return boolean
	 */
	protected function is_admin_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.

		if ( empty( $page ) ) {
			return false;
		}

		/**
		 * Allows filtering the known screens/pages that are regarded as Personalize screens.
		 *
		 * @since 1.0.0
		 *
		 * @param array $pages Personalize page slugs to validate against.
		 */
		$pages = apply_filters(
			'personalizewp_is_pwp_page',
			[
				PERSONALIZEWP_ADMIN_SLUG,
				PERSONALIZEWP_ADMIN_CATEGORIES_SLUG,
				PERSONALIZEWP_ADMIN_RULES_SLUG,
				PERSONALIZEWP_ADMIN_SETTINGS_SLUG,
			]
		);
		// Sanitise the filtered pages.
		$pages = array_filter( array_unique( $pages ) );

		return in_array( $page, $pages, true );
	}

	/**
	 * Detects if current screen is post edit
	 *
	 * @return boolean
	 */
	protected function is_block_editing_page() {
		$screen = get_current_screen();

		if ( is_admin() && $screen->is_block_editor ) {
			return true;
		}

		return false;
	}

	/**
	 * Dismisses Onboarding for all users.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function dismiss_onboarding_message() {
		check_ajax_referer( 'pwp-ajax-nonce' );

		update_option( 'pwp_admin_onboarding_dismissed', '1', true );
		wp_send_json_success( null, 200 );
		exit;
	}
}
