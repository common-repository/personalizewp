<?php
/**
 * Fired during plugin activation
 *
 * @link    https://personalizewp.com
 * @since   1.0.0
 *
 * @package PersonalizeWP
 */

namespace PersonalizeWP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

	/**
	 * Initialise the plugin after activation
	 */
	public static function activate() {
		add_action(
			'activated_plugin',
			function () {
				// Trigger the onboarding wizard to appear, if not already.
				// Uses add_option as doesn't override if option already exists.
				add_option( 'pwp_pending_onboarding', true, '', true );

				// Re-autoload options
				if ( function_exists( 'wp_set_option_autoload_values' ) ) {
					wp_set_options_autoload(
						[
							'_pwp_db_version',
							'personalizewp',
							'pwp_admin_onboarding_dismissed',
							'pwp_pending_onboarding',
						],
						true
					);
				}

				$wp_version = get_bloginfo( 'version' );
				// Check if activated via the short lived AJAX modal, thus no redirect possible.
				if ( version_compare( $wp_version, '6.5', '>=' ) && version_compare( $wp_version, '6.5.4', '<' ) && wp_doing_ajax() ) {
					return;
				}

				// Only apply auto-redirect if not already onboarded (this is set to false upon completion),
				// and if a premium addon isn't installed - As likely to activate that too.
				if ( ! get_option( 'pwp_pending_onboarding' ) || PersonalizeWP::has_premium_installed() ) {
					return;
				}

				$settings_page = admin_url(
					add_query_arg(
						array(
							'page' => 'personalizewp/settings',
						),
						'admin.php'
					)
				);
				wp_safe_redirect( $settings_page );
				exit;
			}
		);
	}
}
