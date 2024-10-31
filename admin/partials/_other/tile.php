<?php
/**
 * View for single tile located in screens
 *
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$has_link   = ! empty( $tile['url'] );
$has_button = ! empty( $tile['button'] );
$has_status = ! empty( $tile['status'] );
$classes    = [ 'tile' ];
if ( $has_link && $has_status ) :
	$classes[] = 'disabled';
endif;
if ( $has_button ) :
	$classes[] = 'has-button';
endif;
?>

<div class="<?php echo implode( ' ', array_map( 'esc_html', $classes ) ); ?>">

	<div class="stack">
		<?php
		if ( ! empty( $tile['img'] ) ) :
			?>
			<img alt="" height="225" width="340" src="<?php echo esc_url( $tile['img'] ); ?>" />
			<?php
		endif;

		// Different overlay banners
		if ( $has_status ) :
			switch ( $tile['status'] ) :
				case 'link':
					printf(
						'<div class="banner link"><a class="btn primary" href="%1$s">%2$s</a></div>',
						esc_url( $tile['url'] ),
						esc_html( $tile['status_text'] )
					);
					break;

				case 'upgrade':
					if ( $has_link ) :
						printf(
							'<div class="banner upgrade"><a class="btn primary" href="%1$s">%2$s</a></div>',
							esc_url( $tile['url'] ),
							esc_html__( 'Upgrade to use', 'personalizewp' )
						);
						break;
					endif;
					// If no link, fallback to coming status.

				case 'coming':
					printf(
						'<div class="banner coming-soon">%1$s</div>',
						esc_html__( 'Coming soon', 'personalizewp' )
					);
					break;

			endswitch;
		endif;
		?>
	</div>

	<div class="tile__content flow">
		<h4 class="tile__title">
			<?php
			// Ensure no double links.
			if ( $has_link && ! $has_button && ! $has_status ) :
				printf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $tile['url'] ),
					esc_html( $tile['title'] )
				);
			else :
				echo esc_html( $tile['title'] );
			endif;
			?>
		</h4>
		<?php

		// Only allow paragraphs as valid content.
		echo wp_kses( wpautop( esc_html( $tile['content'] ) ), [ 'p' => [] ] );

		if ( $has_button && $has_link ) :
			printf(
				'<a class="btn primary" href="%1$s" %2$s>%3$s</a>',
				esc_url( $tile['url'] ),
				str_contains( $tile['url'], home_url() ) ? '' : 'target="_blank"',
				esc_html( $tile['button'] )
			);
		endif;
		?>
	</div>

</div>
