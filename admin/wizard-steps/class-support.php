<?php
/**
 * Onboarding Support
 *
 * @link       https://personalizewp.com
 * @since      2.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Wizard_Steps
 */

namespace PersonalizeWP\Admin\Wizard_Steps;

use PersonalizeWP\Admin\Utils;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Support step class
 */
class Support extends Step {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon = 'tooltip-question';

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Support', 'personalizewp' );
	}

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	public function header() {
		?>
		<h2><?php esc_html_e( 'We’re here to help!', 'personalizewp' ); ?></h2>
		<p><?php esc_html_e( 'We want to ensure your success in using PersonalizeWP, so we’re here and ready to help out. You can:', 'personalizewp' ); ?></p>
		<?php
	}

	/**
	 * Display the main content for the step
	 *
	 * @return void
	 */
	public function display() {
		$base_url = plugins_url( '', __DIR__ );
		?>
			<div class="switcher onboard-cards">
				<div class="onboard-card flow">
					<p>
					<?php
						printf(
							/* translators: 1: %s expands to a website link to PersonalizeWP knowledge base, 2: </a> closing tag. */
							esc_html__( 'Visit our %1$sknowledge base%2$s for tips on how to setup for various use cases.', 'personalizewp' ),
							'<a href="' . esc_url( 'https://personalizewp.com/knowledge-base/' ) . '" target="_blank">',
							'</a>'
						);
					?>
					</p>
				</div>

				<div class="onboard-card flow">
					<p>
					<?php
						printf(
							/* translators: 1: %s expands to a website link to PersonalizeWP support, 2: </a> closing tag. */
							esc_html__( 'Access our %1$ssupport team%2$s if you have a setup or configuration issue.', 'personalizewp' ),
							'<a href="' . esc_url( 'https://personalizewp.com/resources/support/' ) . '" target="_blank">',
							'</a>'
						);
					?>
					</p>
				</div>

				<div class="onboard-card flow">
					<p>
					<?php
						printf(
							/* translators: 1: %s expands to a website link to PersonalizeWP contact, 2: </a> closing tag. */
							esc_html__( 'Talk to our %1$sonboarding team%2$s to discuss how to achieve a specific aim or goal.', 'personalizewp' ),
							'<a href="' . esc_url( 'https://personalizewp.com/contact/' ) . '" target="_blank">',
							'</a>'
						);
					?>
					</p>
				</div>
			</div>

			<p class="text-center"><?php esc_html_e( 'Watch the video below to get an introduction to what PersonalizeWP can do:', 'personalizewp' ); ?></p>

			<div class="stack video" id="onboarding-video">
				<div class="overlay">
					<button type="button" aria-label="<?php esc_html_e( 'Play', 'personalizewp' ); ?>">
						<?php Utils::svg_icon( 'wizard/play' ); ?>
					</button>
				</div>
				<iframe width="600" height="338" loading="lazy" src="https://www.youtube-nocookie.com/embed/Q_gnJVra8Fg" title="<?php esc_html_e( 'Getting started with PersonalizeWP', 'personalizewp' ); ?>" frameborder="0" allowfullscreen></iframe>
			</div>
		<?php
	}
}
