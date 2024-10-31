<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 */

namespace PersonalizeWP;

// If uninstall not called from WordPress, then exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// To ensure that all variables and constants are available.
require __DIR__ . '/includes/personalizewp-constants.php';
require __DIR__ . '/includes/traits/class-singletontrait.php';
require __DIR__ . '/includes/class-db-manager.php';

DB_Manager::instance()->uninstall();
