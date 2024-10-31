<?php
/**
 * PersonalizeWP
 *
 * @link    https://personalizewp.com/
 * @since   2.5.0
 *
 * @package PersonalizeWP
 */

namespace PersonalizeWP;

use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class for managing the database schema and initial data.
 */
class DB_Manager {

	use SingletonTrait;

	/**
	 * DB Schema version.
	 *
	 * @since 1.0.0 started with version string 100.
	 * @since 2.3.0 switched to using direct int.
	 *
	 * @var int
	 */
	protected int $db_version = 260; // Increment with each change in db

	/**
	 * Option key to store database version
	 *
	 * @var string
	 */
	protected string $current_db_key = '_pwp_db_version';

	/**
	 * Run potential installations, populations and migrations
	 */
	public function migrate() {
		if ( ! is_admin() ) {
			return;
		}
		// Double check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_db_version = absint( get_option( $this->current_db_key, 0 ) );
		if ( empty( $current_db_version ) || ! is_numeric( $current_db_version ) ) {
			$current_db_version = 0;
		}

		// We are up to date. Nothing to do.
		if ( $current_db_version === $this->db_version ) {
			return;
		}

		if ( $current_db_version <= 0 ) {
			$results = $this->install();
			if ( ! empty( $results ) ) {
				$this->populate();
			}
		}

		// Run through various upgrade functions, depending on current version.
		if ( $current_db_version < 230 ) {
			$this->upgrade_230( $current_db_version );
		}

		if ( $current_db_version < 240 ) {
			$this->upgrade_240( $current_db_version );
		}

		if ( $current_db_version < 260 ) {
			$this->upgrade_260( $current_db_version );
		}

		// Mark db updated.
		update_option( $this->current_db_key, $this->db_version );
	}

	/**
	 * Executes changes made in 2.3.0.
	 *
	 * @since 2.3.0
	 *
	 * @param int $db_version The old (current) database version.
	 *
	 * @return void
	 */
	private function upgrade_230( $db_version ) {
		global $wpdb;

		if ( $db_version < 230 ) {
			// Re-run install tables to ensure up to date schema.
			// Relies upon core dbDelta() to diff and apply the changes.
			$this->install();

			// Ensure the default condition operator is set for all Rules.
			$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}pwp_rules` SET `operator` = %s", 'ALL' ) );
		}
	}

	/**
	 * Executes changes made in 2.4.0.
	 *
	 * @since 2.4.0
	 *
	 * @param int $db_version The old (current) database version.
	 *
	 * @return void
	 */
	private function upgrade_240( $db_version ) {
		global $wpdb;

		if ( $db_version < 240 ) {
			// Back fill the default condition operator for Rules which might have a blank operator, due to creation bug.
			$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}pwp_rules` SET `operator` = %s WHERE `operator` = ''", 'ALL' ) );
		}
	}

	/**
	 * Executes changes made in 2.6.0.
	 *
	 * @since 2.6.0
	 *
	 * @param int $db_version The old (current) database version.
	 *
	 * @return void
	 */
	private function upgrade_260( $db_version ) {
		global $wpdb;

		if ( $db_version < 260 ) {

			$uses_legacy_id = false;
			// Get table columns
			$results = $wpdb->get_results( "DESC {$wpdb->prefix}pwp_active_blocks" );
			foreach ( $results as $row ) {

				if ( 'id' === $row->Field ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$uses_legacy_id = true;
					break;
				}
			}
			if ( $uses_legacy_id ) {
				// Renames id column and allow strings not just int.
				$wpdb->query( "ALTER TABLE `{$wpdb->prefix}pwp_active_blocks` CHANGE `id` `block_ref` varchar(255) NOT NULL DEFAULT '0' FIRST;" );
			}

			// Re-run install tables to ensure up to date schema.
			// Relies upon core dbDelta() to diff and apply the changes.
			$this->install();
		}
	}

	/**
	 * Install database tables
	 *
	 * @since 1.0.0
	 *
	 * @return array Strings containing the results of the various creation queries. Empty if tables already there.
	 */
	private function install() {
		global $wpdb;

		$wpdb->hide_errors();

		// Ensure we have access to dbDelta()
		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		$sql             = array();
		$charset_collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$sql[] = "CREATE TABLE {$wpdb->prefix}pwp_categories (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL DEFAULT '',
				`created_at` datetime NOT NULL,
				`modified_at` datetime NOT NULL,
				PRIMARY KEY (`id`)
			) {$charset_collate};";

		$sql[] = "CREATE TABLE {$wpdb->prefix}pwp_rules (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL DEFAULT '',
				`category_id` int(11) NOT NULL,
				`type` varchar(30) NOT NULL,
				`conditions_json` mediumtext NOT NULL,
				`operator` varchar(10) NOT NULL DEFAULT 'ALL',
				`created_by` bigint(20) NOT NULL,
				`created_at` datetime NOT NULL,
				`modified_at` datetime NOT NULL,
				PRIMARY KEY (`id`),
				KEY category_id (`category_id`),
				KEY `type` (`type`)
			) {$charset_collate};";

		$sql[] = "CREATE TABLE {$wpdb->prefix}pwp_active_blocks (
				`block_ref` varchar(255) NOT NULL,
				`rule_id` bigint(20) unsigned NOT NULL default 0,
				`post_id` bigint(20) unsigned NOT NULL default 0,
				`name` varchar(255) NOT NULL DEFAULT '',
				PRIMARY KEY (`block_ref`, `rule_id`, `post_id`),
				KEY block_ref (`block_ref`),
				KEY rule_id (`rule_id`),
				KEY post_id (`post_id`)
			) {$charset_collate};";

		$sql[] = "CREATE TABLE {$wpdb->prefix}pwp_block_mappings (
				`block_ref` varchar(255) NOT NULL,
				`post_ref` varchar(255) NOT NULL,
				`map_type` varchar(255) NOT NULL DEFAULT '',
				PRIMARY KEY (`block_ref`),
				KEY post_ref (`post_ref`),
				KEY map_type (`map_type`)
			) {$charset_collate};";

		$db_delta_results = \dbDelta( $sql );

		$wpdb->show_errors();

		return $db_delta_results;
	}

	/**
	 * Populate database tables with initial content
	 *
	 * @since 1.0.0
	 *
	 * @return array Strings containing the results of the various delta queries.
	 */
	private function populate() {
		global $wpdb;

		// Ensure we have access to dbDelta()
		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		$sql   = array();
		$sql[] = "INSERT INTO `{$wpdb->prefix}pwp_categories` (`id`, `name`, `created_at`, `modified_at`)
            VALUES
                (NULL, 'Location', NOW(), NOW()),
                (NULL, 'Purchases', NOW(), NOW()),
                (NULL, 'User Types', NOW(), NOW()),
                (NULL, 'Other', NOW(), NOW()),
				(NULL, 'Device Types', NOW(), NOW()),
                (NULL, 'Time', NOW(), NOW());
			";

		$sql[] = "INSERT INTO `{$wpdb->prefix}pwp_rules` (`id`, `name`, `category_id`, `type`, `conditions_json`, `created_by`, `created_at`, `modified_at`)
			VALUES
				(NULL, 'User is currently logged in', 3, 'standard', '[{\"measure\":\"core_is_logged_in_user\",\"comparator\":\"equals\",\"value\":\"true\"}]', 0, NOW(), NOW()),
				(NULL, 'User is not logged in', 3, 'standard', '[{\"measure\":\"core_is_logged_in_user\",\"comparator\":\"equals\",\"value\":\"false\"}]', 0, NOW(), NOW()),
				(NULL, 'UK-based visitor', 1, 'standard', '[{\"measure\":\"core_visitor_country\",\"comparator\":\"equals\",\"value\":\"GB\"}]', 0, NOW(), NOW()),
				(NULL, 'New visitor', 3, 'standard', '[{\"measure\":\"core_new_visitor\",\"comparator\":\"equals\",\"value\":\"true\"}]', 0, NOW(), NOW()),
				(NULL, 'Returning visitor', 3, 'standard', '[{\"measure\":\"core_new_visitor\",\"comparator\":\"equals\",\"value\":\"false\"}]', 0, NOW(), NOW()),
				(NULL, 'Device Type - mobile', 5, 'standard', '[{\"measure\":\"core_users_device_type\",\"comparator\":\"equals\",\"value\":\"mobile\"}]', 0, NOW(), NOW()),
				(NULL, 'Device Type - desktop', 5, 'standard', '[{\"measure\":\"core_users_device_type\",\"comparator\":\"equals\",\"value\":\"desktop\"}]', 0, NOW(), NOW()),
				(NULL, '10 secs spent on page', 6, 'standard', '[{\"measure\":\"core_time_elapsed\",\"comparator\":\"equals\",\"value\":\"10\"}]', 0, NOW(), NOW()),
				(NULL, '30 secs spent on page', 6, 'standard', '[{\"measure\":\"core_time_elapsed\",\"comparator\":\"equals\",\"value\":\"30\"}]', 0, NOW(), NOW()),
				(NULL, '1 min spent on page', 6, 'standard', '[{\"measure\":\"core_time_elapsed\",\"comparator\":\"equals\",\"value\":\"60\"}]', 0, NOW(), NOW()),
				(NULL, 'US-based visitor', 1, 'standard', '[{\"measure\":\"core_visitor_country\",\"comparator\":\"equals\",\"value\":\"US\"}]', 0, NOW(), NOW()),
				(NULL, 'Time is morning (6am - 12pm)', 6, 'standard', '[{\"measure\":\"core_users_visiting_time\",\"comparator\":\"equals\",\"value\":\"morning\"}]', 0, NOW(), NOW()),
				(NULL, 'Time is afternoon (12pm - 6pm)', 6, 'standard', '[{\"measure\":\"core_users_visiting_time\",\"comparator\":\"equals\",\"value\":\"afternoon\"}]', 0, NOW(), NOW()),
				(NULL, 'Time is evening (6pm - 12am)', 6, 'standard', '[{\"measure\":\"core_users_visiting_time\",\"comparator\":\"equals\",\"value\":\"evening\"}]', 0, NOW(), NOW());
			";

		return \dbDelta( $sql );
	}

	/**
	 * Remove all the PersonalizeWP data when uninstalling
	 */
	public function uninstall() {
		global $wpdb;

		// Remove all custom tables.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- We are uninstalling the plugin
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pwp_rules" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pwp_categories" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pwp_active_blocks" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pwp_block_mapping" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

		// Remove all keys in options table.
		delete_option( $this->current_db_key );
		delete_option( 'pwp_admin_notices' );
		delete_option( 'pwp_flash_messages' );
		delete_option( 'pwp_editor_role_values' );
		delete_option( 'personalizewp_editor_role_values' );
		delete_option( 'dxp_newsletter_signup' );
		delete_option( 'pwp_admin_dashboard_message' );
		delete_option( 'pwp_admin_onboarding_message' );
		delete_option( 'pwp_admin_onboarding_dismissed' );
		delete_option( 'pwp_pending_onboarding' );
	}
}
