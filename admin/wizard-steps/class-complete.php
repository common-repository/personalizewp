<?php
/**
 * Onboarding Complete
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
 * Complete onboarding class
 */
class Complete extends Step {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon = 'circle-tick';

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Complete', 'personalizewp' );
	}

	/**
	 * Return the text of the next step button
	 *
	 * @return string
	 */
	public function get_next_button_text() {
		return __( 'Start now', 'personalizewp' );
	}

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	public function header() {
		?>
		<h2><?php esc_html_e( 'That’s it - you’re ready to get going!', 'personalizewp' ); ?></h2>
		<p><?php esc_html_e( 'You’re now on your way to being able to personalize your content for your visitors and provide them a improved experience.', 'personalizewp' ); ?></p>
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
			<img class="aligncenter" alt="" height="682" width="695" src="<?php echo esc_url( $base_url . '/img/wizard/onboard-8.png' ); ?>">
		<?php
	}
}
