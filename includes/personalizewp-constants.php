<?php
/**
 * Constants used within PersonalizeWP
 *
 * @link    https://personalizewp.com
 * @since   1.0.0
 *
 * @package PersonalizeWP
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Slugs
define( 'PERSONALIZEWP_ADMIN_SLUG', 'personalizewp' );
define( 'PERSONALIZEWP_ADMIN_SETTINGS_SLUG', 'personalizewp/settings' );
define( 'PERSONALIZEWP_ADMIN_KNOWLEDGE_BASE_SLUG', 'personalizewp/knowledge-base' );
define( 'PERSONALIZEWP_ADMIN_CATEGORIES_SLUG', 'personalizewp/categories' );
define( 'PERSONALIZEWP_ADMIN_RULES_SLUG', 'personalizewp/rules' );
define( 'PERSONALIZEWP_ADMIN_URL', get_admin_url() );

// Pages
define( 'PERSONALIZEWP_ADMIN_DASHBOARD_INDEX_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_SLUG );

define( 'PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_CATEGORIES_SLUG );
define( 'PERSONALIZEWP_ADMIN_CATEGORIES_CREATE_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_CATEGORIES_SLUG . '&personalizewp_action=create' );
define( 'PERSONALIZEWP_ADMIN_CATEGORIES_EDIT_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_CATEGORIES_SLUG . '&personalizewp_action=edit&id=' );
define( 'PERSONALIZEWP_ADMIN_CATEGORIES_DELETE_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_CATEGORIES_SLUG . '&personalizewp_action=delete&id=' );
define( 'PERSONALIZEWP_ADMIN_CATEGORIES_SHOW_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_CATEGORIES_SLUG . '&personalizewp_action=show&id=' );
define( 'PERSONALIZEWP_ADMIN_CATEGORIES_RULES_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_CATEGORIES_SLUG . '&personalizewp_action=rules&id=' );

define( 'PERSONALIZEWP_ADMIN_RULES_INDEX_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_RULES_SLUG );
define( 'PERSONALIZEWP_ADMIN_RULES_CREATE_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_RULES_SLUG . '&personalizewp_action=create' );
define( 'PERSONALIZEWP_ADMIN_RULES_EDIT_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_RULES_SLUG . '&personalizewp_action=edit&id=' );
define( 'PERSONALIZEWP_ADMIN_RULES_DELETE_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_RULES_SLUG . '&personalizewp_action=delete&id=' );
define( 'PERSONALIZEWP_ADMIN_RULES_DUPLICATE_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_RULES_SLUG . '&personalizewp_action=duplicate&id=' );
define( 'PERSONALIZEWP_ADMIN_RULES_SHOW_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_RULES_SLUG . '&personalizewp_action=show&id=' );

define( 'PERSONALIZEWP_ADMIN_SETTINGS_INDEX_URL', PERSONALIZEWP_ADMIN_URL . 'admin.php?page=' . PERSONALIZEWP_ADMIN_SETTINGS_SLUG );

