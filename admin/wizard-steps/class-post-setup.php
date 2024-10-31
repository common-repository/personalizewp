<?php
/**
 * Onboarding Post Personalize Setup
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
 * Post setup class
 */
class Post_Setup extends Step {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon = 'cog';

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Setup', 'personalizewp' );
	}

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	public function header() {
		?>
		<h2><?php esc_html_e( 'Thanks for letting us know!', 'personalizewp' ); ?></h2>
		<p><?php esc_html_e( 'Based on your feedback, we’ve set the following up for you:', 'personalizewp' ); ?></p>
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
			<p><?php esc_html_e( 'These are ready for you to use in the plugin to get you started. But don’t forget you can create your own to match your needs.', 'personalizewp' ); ?></p>

			<div class="grid onboard-cards">
				<div class="onboard-card flow">
					<img alt="" height="285" width="450" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-5.png' ); ?>">
					<p><?php esc_html_e( 'Custom Personalization Rules', 'personalizewp' ); ?></p>
				</div>

				<div class="onboard-card flow">
					<img alt="" height="285" width="450" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-6.png' ); ?>">
					<p><?php esc_html_e( 'Recommended Segments', 'personalizewp' ); ?></p>
				</div>

				<div class="onboard-card flow">
					<img alt="" height="285" width="450" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-7.png' ); ?>">
					<p><?php esc_html_e( 'Suggested Lead Scores', 'personalizewp' ); ?></p>
				</div>
			</div>
		<?php
	}
}
