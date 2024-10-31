<?php
/**
 * Abstract class for all onboarding wizard steps.
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
 * Step class
 */
abstract class Step extends \stdClass {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * Return the icon name to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_progress_icon() {
		return $this->icon;
	}

	/**
	 * Return the text of the next step button
	 *
	 * @return string
	 */
	public function get_next_button_text() {
		return __( 'Next', 'personalizewp' );
	}

	/**
	 * Display a skip button for the current step
	 *
	 * @return bool
	 */
	public function display_skip_button() {
		return false;
	}

	/**
	 * Store processed onboarding step data, merging with what is there.
	 *
	 * @param array $data Data to store within shared onboarding option
	 *
	 * @return void
	 */
	protected function store_data( $data ) {

		if ( empty( $data ) ) {
			return;
		}

		$onboarding_data = array_merge( get_option( 'pwp_onboarding', [] ), $data );

		update_option( 'pwp_onboarding', $onboarding_data, false );
	}

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	abstract public function header();

	/**
	 * Display the main content for the step
	 *
	 * @return void
	 */
	abstract public function display();
}
