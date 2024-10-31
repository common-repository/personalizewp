<?php
/**
 * Custom List Table for displaying Rules used across Blocks in a table format.
 *
 * @link       https://personalizewp.com
 * @since      2.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

use PersonalizeWP\Admin\Utils;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Onboarding Wizard
 */
class Onboarding_Wizard {

	/**
	 * The step classes for onboarding
	 *
	 * @var array
	 */
	private $step_classes = [];

	/**
	 * Setup the class and its properties and actions.
	 *
	 * @since 2.0.0
	 */
	public function setup() {

		// Register the option within WP.
		add_action( 'admin_init', [ $this, 'register_option' ] );
		add_action( 'admin_action_pwp_onboarding_complete', [ $this, 'complete_all_onboarding' ] );

		add_action( 'wp_ajax_pwp_onboarding_wizard', [ $this, 'process_step' ] );
		add_action( 'wp_ajax_pwp_onboarding_close', [ $this, 'process_close' ] );

		add_action( 'personalizewp_footer', [ $this, 'display' ] );
	}

	/**
	 * Loads all the steps, if not already loaded, "5 6 7 8".
	 *
	 * @return void
	 */
	private function load_steps() {

		if ( ! empty( $this->step_classes ) ) {
			return;
		}

		foreach ( $this->get_sorted_step_names() as $step_name ) {
			$class = __NAMESPACE__ . '\Wizard_Steps\\' . ucwords( $step_name, '_' );
			if ( class_exists( $class ) ) {
				$this->step_classes[ strtolower( $step_name ) ] = new $class();
			}
		}
	}

	/**
	 * Provide an ordered list of the onboarding steps
	 *
	 * @return array
	 */
	public function get_sorted_step_names() {
		return array(
			'Welcome',
			'Personalize',
			'Post_Setup',
			'Support',
			'Newsletter',
			'Complete',
		);
	}

	/**
	 * Registers our Onboarding settings in the WP options table
	 */
	public function register_option() {
		register_setting(
			'personalizewp',
			'personalizewp_onboarding',
			array(
				'type'              => 'array',
				'show_in_rest'      => false,
				'default'           => [],
				'sanitize_callback' => function ( $opt ) {
					return array_map( 'sanitize_text_field', $opt );
				},
			)
		);
	}

	/**
	 * Completes the onboarding, running any final processes for each step.
	 *
	 * @return void
	 */
	public function complete_all_onboarding() {

		check_admin_referer( 'pwp-onboarding-complete' );

		// Check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// All the data stored via each step.
		$onboarding_data = get_option( 'pwp_onboarding' );

		$this->load_steps();
		foreach ( $this->step_classes as $step ) {
			if ( method_exists( $step, 'complete' ) ) {
				$step->complete( $onboarding_data );
			}
		}

		// Stop showing the wizard.
		update_option( 'pwp_pending_onboarding', false, true );

		/**
		 * Fires once all individual steps have completed their post onboarding.
		 *
		 * @since 2.0.0
		 *
		 * @param array $onboarding_data The data recorded during onboarding steps.
		 */
		do_action( 'pwp_onboarding_complete', $onboarding_data );

		// Redirect to main Dashboard.
		wp_safe_redirect(
			admin_url(
				add_query_arg(
					array(
						'page'                => 'personalizewp',
						'onboarding_complete' => '1',
					),
					'admin.php'
				)
			)
		);
	}

	/**
	 * Process the onboarding wizard closing without finishing
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function process_close() {

		check_ajax_referer( 'pwp-onboarding-close' );

		// Check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		// Just stop showing the wizard
		update_option( 'pwp_pending_onboarding', false, true );

		wp_send_json_success( null, 200 );
	}

	/**
	 * Process any interstitial forms within the onboarding wizard
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function process_step() {

		check_ajax_referer( 'pwp-onboarding-wizard' );

		// Check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$step      = (int) sanitize_text_field( wp_unslash( $_POST['step'] ) );
		$form_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['pwp_onboarding'] ) );
		if ( ! empty( $form_data ) ) {
			$response = $this->process_step_form( $step, $form_data );

			if ( ! is_wp_error( $response ) ) {
				wp_send_json_success( null, 200 );
			}
		}

		wp_send_json_error();
	}

	/**
	 * Validates for individual processing within a step, then processes the interstitial form.
	 *
	 * @param int   $step      Step that the form was part of
	 * @param array $form_data Pre sanitized form data
	 *
	 * @return bool|\WP_Error True on success, WP_Error otherwise
	 */
	private function process_step_form( $step, $form_data ) {

		$this->load_steps();

		$class_names = array_keys( $this->step_classes );
		// Rationalize to start from zero.
		--$step;
		// Check within valid range of steps.
		if ( $step < 0 || $step > count( $class_names ) ) {
			return false;
		}
		// Get the actual class name
		$class_name = $class_names[ $step ];
		if ( empty( $this->step_classes[ $class_name ] ) ) {
			return false;
		}
		// Check step can process the form
		$current_step = $this->step_classes[ $class_name ];
		if ( method_exists( $current_step, 'process' ) ) {
			return $current_step->process( $form_data );
		}
		return false;
	}

	/**
	 * Display the step progression via icons
	 *
	 * @return void
	 */
	private function display_progress_icons() {

		$total_steps = count( $this->step_classes );
		?>
		<ol id="pwp-progress-bar" class="wizard-progress icons">
		<?php
		$current = 0;
		foreach ( $this->step_classes as $step ) {
			++$current;
			if ( $current === $total_steps ) {
				// Last step just output the icon.
				printf(
					'<li data-step="%2$d">%1$s</li>',
					Utils::get_svg_icon( 'wizard/' . $step->get_progress_icon() ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: function output already escaped
					(int) $current
				);
				continue;
			}

			printf(
				'<li class="%4$s" data-step="%3$d"><button disabled type="button" class="button" aria-label="%1$s" value="%3$d">%2$s</button></li>',
				esc_attr( $step->get_title() ),
				Utils::get_svg_icon( 'wizard/' . $step->get_progress_icon() ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: function output already escaped
				(int) $current,
				( 1 === $current ) ? 'is-active' : ''
			);
		}
		?>
		</ol>
		<?php
	}

	/**
	 * Display the step progression via dots
	 *
	 * @return void
	 */
	private function display_progress_dots() {

		$total_steps = count( $this->step_classes );
		?>
		<ol id="pwp-progress-dots" class="wizard-progress dots">
		<?php
		$current = 0;
		foreach ( $this->step_classes as $step ) {
			++$current;
			printf(
				'<li class="%2$s" data-step="%1$d">&dot;</li>',
				(int) $current,
				( 1 === $current ) ? 'is-active' : ''
			);
		}
		?>
		</ol>
		<?php
	}

	/**
	 * Display the onboarding
	 */
	public function display() {

		$this->load_steps();

		$properties = require plugin_dir_path( __FILE__ ) . 'js/admin-onboarding-wizard.asset.php';
		wp_enqueue_script( 'personalizewp-onboarding', plugin_dir_url( __FILE__ ) . 'js/admin-onboarding-wizard.js', $properties['dependencies'], $properties['version'], [ 'in_footer' => true ] );

		// You've started, so ensure we go through all the steps and finish.
		update_option( 'pwp_pending_onboarding', true, true );
		?>

<dialog id="pwp-onboarding-wizard" class="dialog onboarding">
<div class="wrapper">
	<aside>
		<nav>
			<form method="dialog" class="dialog-close" action="" method="post">
				<input type="hidden" name="action" value="pwp_onboarding_close" />
				<?php wp_nonce_field( 'pwp-onboarding-close', '_ajax_nonce' ); ?>

				<button type="submit" value="close">
					<?php Utils::svg_icon( 'cross' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Close', 'personalizewp' ); ?></span>
				</button>
			</form>
			<?php $this->display_progress_icons(); ?>
		</nav>
	</aside>

	<article class="steps" aria-live="polite">
		<?php
		$total_steps = count( $this->step_classes );
		$step_num    = 0;
		foreach ( $this->step_classes as $step_name => $step ) :
			++$step_num;
			?>
			<section class="step <?php echo esc_attr( $step_name ); ?>" data-step="<?php echo (int) $step_num; ?>"
				<?php echo esc_attr( 1 < $step_num ? 'hidden' : '' ); ?>>
				<header>
					<div class="header-text">
						<?php
						// Output the step header
						$step->header();
						?>
					</div>
					<img src="<?php echo esc_url( plugins_url( '/img/personalizewp-logo-sm.svg', __FILE__ ) ); ?>" height="18" width="34" alt="" />
				</header>

				<div class="content">
					<?php
					// Display whatever the step needs to output.
					$step->display();

					/**
					 * Fires as a specific Onboarding Step has been outputted.
					 *
					 * The dynamic portion of the hook name, `$step_name`,
					 * refers to the name of the step that has just outputted.
					 *
					 * @since 2.0.0
					 *
					 * @param object $step Class of the current step
					 */
					do_action( "personalizewp_onboarding_step_content_{$step_name}", $step );
					?>
				</div>

				<footer class="next-step actions" aria-live="polite">
					<?php
					$button_text = $step->get_next_button_text();
					/**
					 * Filters the button text used to go to the next step of onboarding
					 *
					 * @since 2.0.0
					 *
					 * @param string $button_text Button text
					 * @param string $step_name   Current step name
					 * @param object $step        Class of the current step
					 */
					$button_text = apply_filters( 'personalizewp_onboarding_next_button_text', $button_text, $step_name, $step );
					if ( $total_steps !== $step_num ) :

						/**
						 * Allow displaying a skip step button
						 *
						 * Used when a step has an ajax form that is separately submitted, successful submission causes the original button to be displayed.
						 *
						 * @since 2.1.0
						 *
						 * @param bool   $disabled  Whether two buttons should be toggled between after form submission.
						 * @param string $step_name Current step name
						 * @param object $step      Class of the current step
						 */
						if ( apply_filters( 'personalizewp_onboarding_allow_skip_step', $step->display_skip_button(), $step_name, $step ) ) :
							?>
							<button class="btn secondary" type="button" value="next"><?php esc_html_e( 'Skip this step', 'personalizewp' ); ?></button>
							<button hidden class="btn primary" type="button" value="next"><?php echo esc_html( $button_text ); ?></button>
							<?php
						else :
							?>
							<button class="btn primary" type="button" value="next"><?php echo esc_html( $button_text ); ?></button>
							<?php
						endif;
					else :
						?>
						<form action="admin.php" method="post">
							<input type="hidden" name="action" value="pwp_onboarding_complete" />
							<?php wp_nonce_field( 'pwp-onboarding-complete' ); ?>
							<button class="btn primary" type="submit" value="complete"><?php echo esc_html( $button_text ); ?></button>
						</form>
						<?php
					endif;
					?>
				</footer><!-- next-step -->
			</section>
			<?php
		endforeach;
		?>
	</article>

	<footer>
		<?php $this->display_progress_dots(); ?>
	</footer>
</div>
</dialog>

		<?php
	}
}
