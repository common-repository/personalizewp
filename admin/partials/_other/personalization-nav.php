<?php
/**
 * View for shared navigation within Rules/Categories
 *
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$current_page  = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- no site changes here.
$is_rules_page = PERSONALIZEWP_ADMIN_RULES_SLUG === $current_page;
$is_cats_page  = PERSONALIZEWP_ADMIN_CATEGORIES_SLUG === $current_page;
?>

			<nav class="header-nav">
				<ul class="nav-list">
					<li>
						<a class="nav-link <?php echo $is_rules_page ? esc_attr( 'is-active' ) : ''; ?>" href="<?php echo esc_url( PERSONALIZEWP_ADMIN_RULES_INDEX_URL ); ?>"> <?php esc_html_e( 'Rules', 'personalizewp' ); ?></a>
					</li>
					<li>
						<a class="nav-link <?php echo $is_cats_page ? esc_attr( 'is-active' ) : ''; ?>" href="<?php echo esc_url( PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL ); ?>"> <?php esc_html_e( 'Categories', 'personalizewp' ); ?></a>
					</li>
				</ul>
			</nav>
