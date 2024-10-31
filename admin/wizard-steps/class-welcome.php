<?php
/**
 * Onboarding Welcome
 *
 * @link       https://personalizewp.com
 * @since      2.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Wizard_Steps
 */

namespace PersonalizeWP\Admin\Wizard_Steps;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Initial onboarding step class
 */
class Welcome extends Step {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon = 'hand-wave';

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Welcome', 'personalizewp' );
	}

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	public function header() {
		?>
		<h2><?php esc_html_e( 'Welcome to PersonalizeWP', 'personalizewp' ); ?></h2>
		<p><?php esc_html_e( 'Thank you for choosing to use PersonalizeWP on your site. We know that you’re going to find it really useful!', 'personalizewp' ); ?></p>
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
			<div class="grid onboard-cards">
				<div class="onboard-card flow">
					<img alt="" height="317" width="390" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-1.png' ); ?>">
					<p><?php esc_html_e( 'Show and hide every block', 'personalizewp' ); ?></p>
				</div>

				<div class="onboard-card flow">
					<img alt="" height="317" width="390" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-2.png' ); ?>">
					<p><?php esc_html_e( 'Use pre-built and custom rules', 'personalizewp' ); ?></p>
				</div>

				<div class="onboard-card flow">
					<img alt="" height="317" width="390" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-3.png' ); ?>">
					<p><?php esc_html_e( 'Collect data and build visitor profiles', 'personalizewp' ); ?></p>
				</div>

				<div class="onboard-card flow">
					<img alt="" height="317" width="390" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-4.png' ); ?>">
					<p><?php esc_html_e( 'Segment users by their behaviour', 'personalizewp' ); ?></p>
				</div>
			</div>
		<?php
	}
}
