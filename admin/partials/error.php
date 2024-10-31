<?php
/**
 * Display an error message
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
