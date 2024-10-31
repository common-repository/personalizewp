<?php
/**
 * Main Admin page header template
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Filters the list of CSS class names for the root WP-DXP element.
 *
 * @since 1.0.0
 *
 * @param string[] $classes An array of class names.
 */
$body_classes = apply_filters( 'personalizewp_body_class', array() );
?>
<!-- CSS namespace -->
<div id="wp-dxp" class="<?php echo esc_attr( implode( ' ', $body_classes ) ); ?>">

<header class="wp-dxp-header page-<?php echo esc_attr( sanitize_title( $page ) ); ?>">
	<div  class="wp-dxp-breadcrumbs">
		<h1 class="wp-dxp-breadcrumbs__title">
			<img src="<?php echo esc_url( $base_url . '/img/personalizewp-logo.svg' ); ?>" alt="<?php esc_html_e( 'PersonalizeWP', 'personalizewp' ); ?>" />
		</h1>
		<?php
		if ( ! empty( $page_title ) ) :
			?>
			<span class="wp-dxp-breadcrumbs__separator">/</span>
			<span class="wp-dxp-breadcrumbs__page"><?php echo esc_html( $page_title ); ?></span>
			<?php
		endif;
		?>
	</div>

	<div class="wp-dxp-header__links">
		<a href="<?php echo esc_url( 'https://personalizewp.com/knowledge-base/' ); ?>" target="_blank" class="wp-dxp-button wp-dxp-button--white">
			<span class="dashicons dashicons-editor-help"></span>
			<?php esc_html_e( 'Knowledge Base', 'personalizewp' ); ?>
		</a>

		<a href="<?php echo esc_url( 'https://personalizewp.com/resources/support/' ); ?>" target="_blank" class="wp-dxp-button wp-dxp-button--white">
			<span class="dashicons dashicons-format-chat"></span>
			<?php esc_html_e( 'Support', 'personalizewp' ); ?>
		</a>

		<a href="<?php echo esc_url( 'https://en-gb.wordpress.org/plugins/personalizewp/#reviews' ); ?>" target="_blank" class="wp-dxp-button wp-dxp-button--white">
			<span class="dashicons dashicons-thumbs-up"></span>
			<?php esc_html_e( 'Leave a Review', 'personalizewp' ); ?>
		</a>
	</div>
</header>

<hr class="wp-header-end">
