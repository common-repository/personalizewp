<?php
/**
 * View for shared navigation within Rules/Categories
 *
 * @since      2.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

use PersonalizeWP\Admin\Utils;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

<form class="ajax-form pwp-newsletter-signup flow" action="" method="post">
	<h2 class="mb-2"><?php esc_html_e( 'Join our email list', 'personalizewp' ); ?></h2>

	<input type="hidden" name="action" value="pwp_newsletter_signup" />
	<?php wp_nonce_field( 'newsletter-signup', '_ajax_nonce' ); ?>

	<p>
		<?php
			printf(
				/* translators: 1: %s expands to a website link to PersonalizeWP, 2: </a> closing tag. */
				esc_html__( 'Sign up to receive our newsletter and updates, providing you with helpful hints and tips about how to get the most out of PersonalizeWP. Submission of the newsletter signup form is sent to our %1$swebsite%2$s to be processed.', 'personalizewp' ),
				'<a href="' . esc_url( 'https://personalizewp.com/' ) . '" target="_blank">',
				'</a>'
			);
			?>
	</p>

	<div class="switcher fields">
		<div>
			<label for="input-first-name"><?php esc_html_e( 'First name', 'personalizewp' ); ?></label>
			<input id="input-first-name" type="text" required name="first_name" value="" />
		</div>
		<div>
			<label for="input-last-name"><?php esc_html_e( 'Last name', 'personalizewp' ); ?></label>
			<input id="input-last-name" type="text" required name="last_name" value="" />
		</div>

		<div class="email">
			<label for="input-email-address"><?php esc_html_e( 'Email address', 'personalizewp' ); ?></label>
			<input id="input-email-address" type="email" required name="email_address" value="" />
		</div>

		<div class="newsletter-terms">
			<?php
			$input_id = wp_unique_id( 'input-terms' );
			?>
			<label for="<?php echo esc_attr( $input_id ); ?>">
				<input id="<?php echo esc_attr( $input_id ); ?>" type="checkbox" required name="terms_acceptance" value="1" />
				<?php
				printf(
					/* translators: 1: %s expands to a website link to PersonalizeWP Terms and Conditions, 2: </a> closing tag. */
					esc_html__( 'I accept the %1$sTerms and Conditions%2$s', 'personalizewp' ),
					'<a href="' . esc_url( 'https://personalizewp.com/terms/' ) . '" target="_blank">',
					'</a>'
				);
				?>
			</label>
		</div>
	</div>

	<div class="actions">
		<button class="btn primary" type="submit"><?php esc_html_e( 'Sign Up', 'personalizewp' ); ?></button>
		<?php
		if ( ! empty( $is_onboarding_wizard ) ) :
			?>
			<button class="btn secondary" type="button"><?php esc_html_e( 'Skip this step', 'personalizewp' ); ?></button>
			<?php
		endif;
		?>
		<button hidden class="btn secondary" type="button">
		<?php
		esc_html_e( 'Submitted', 'personalizewp' );
		Utils::svg_icon( 'check' );
		?>
		</button>
	</div>

	<div class="confirmation" hidden>
		<p>
			<?php esc_html_e( 'Thank you for submitting your details!', 'personalizewp' ); ?>
			<em><?php esc_html_e( 'Please check your inbox for a confirmation email and further instructions.', 'personalizewp' ); ?></em>
		</p>
	</div>
</form>

