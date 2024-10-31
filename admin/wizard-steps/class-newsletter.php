<?php
/**
 * Onboarding Newsletter Signup
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
 * Newsletter signup class
 */
class Newsletter extends Step {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon = 'email-newsletter';

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Newsletter', 'personalizewp' );
	}

	/**
	 * Display a skip button for the current step
	 *
	 * @return bool
	 */
	public function display_skip_button() {
		return true;
	}

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	public function header() {
		?>
		<h2><?php esc_html_e( 'Sign up for our newsletter', 'personalizewp' ); ?></h2>
		<p><?php esc_html_e( 'Finally, we would like to be able to occasionally send you news about updates to PersonalizeWP and helpful articles and guides on how to implement new features.', 'personalizewp' ); ?></p>
		<?php
	}

	/**
	 * Display the main content for the step
	 *
	 * @return void
	 */
	public function display() {
		include plugin_dir_path( __DIR__ ) . '/partials/_other/newsletter-signup.php';
	}
}
