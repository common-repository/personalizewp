<?php
/**
 * PersonalizeWP Plugin.
 *
 * @package   PersonalizeWP
 * @copyright Copyright (C) 2020-2024, PersonalizeWP - support@personalizewp.com
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name:       PersonalizeWP
 * Plugin URI:        https://personalizewp.com/
 * Description:       Use PersonalizeWP to add personalization and conditional rules to the content that your users see and can interact with in the Block Editor. Use our preset rules or add your own so that you can match user behavior and show or hide blocks based on if your conditions are met.
 * Version:           2.6.0
 * Author:            PersonalizeWP
 * Author URI:        https://personalizewp.com/
 * Requires at least: 6.0.0
 * Requires PHP:      7.4
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       personalizewp
 * Domain Path:       /languages/
 *
 * PersonalizeWP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PersonalizeWP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PersonalizeWP. If not, see <http://www.gnu.org/licenses/>.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Current plugin version.
 */
define( 'PERSONALIZEWP_VERSION', '2.6.0' );

// Load autoloader.
require __DIR__ . '/includes/class-autoloader.php';
new PersonalizeWP\Autoloader( __DIR__ );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-personalizewp-activator.php
 */
function activate_personalizewp() {
	\PersonalizeWP\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-personalizewp-deactivator.php
 */
function deactivate_personalizewp() {
	\PersonalizeWP\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_personalizewp' );
register_deactivation_hook( __FILE__, 'deactivate_personalizewp' );

/**
 * Returns the main instance of PersonalizeWP.
 *
 * @since 1.0.0
 * @return PersonalizeWP
 */
function personalizewp() {
	return \PersonalizeWP\PersonalizeWP::instance();
}
\PersonalizeWP\PersonalizeWP::instance()->setup( __FILE__ );
