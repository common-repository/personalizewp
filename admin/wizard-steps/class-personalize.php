<?php
/**
 * Onboarding Personalize
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
 * Personalize onboarding class
 */
class Personalize extends Step {

	/**
	 * Icon, used in Progress sidebar
	 *
	 * @var string
	 */
	protected $icon = 'web';

	/**
	 * The options for the 'describe your website' form field.
	 *
	 * @var array
	 */
	protected $description_opts;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->description_opts = [
			__( 'Content / Publishing', 'personalizewp' ),
			__( 'E-commerce', 'personalizewp' ),
			__( 'Membership', 'personalizewp' ),
			__( 'Events', 'personalizewp' ),
			__( 'Non-profit', 'personalizewp' ),
			__( 'Brochure', 'personalizewp' ),
			__( 'Portfolio', 'personalizewp' ),
			__( 'Blog', 'personalizewp' ),
		];
	}

	/**
	 * Return the step title to be used for the progress bar
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Personalize', 'personalizewp' );
	}

	/**
	 * Display the header content for the step
	 *
	 * @return void
	 */
	public function header() {
		?>
		<h2><?php esc_html_e( 'Help us personalize your experience', 'personalizewp' ); ?></h2>
		<p><?php esc_html_e( 'It will help us if you can tell us more about what type of site you have and how you plan to use PersonalizeWP.', 'personalizewp' ); ?></p>
		<?php
	}

	/**
	 * Complete the personalisation, now onboarding is complete
	 *
	 * @param array $onboarding Data recorded during onboarding
	 *
	 * @return bool|\WP_Error
	 */
	public function complete( $onboarding ) {

		// Add new Rule based on the selected Country.
		if ( ! empty( $onboarding['country'] ) ) {
			$countries = \personalizewp()->get_countries();
			if ( in_array( $onboarding['country'], array_keys( $countries ), true ) ) {
				$rule_data  = [
					'name'        => sprintf(
						/* translators: %s Country name */
						__( '%s based visitor', 'personalizewp' ),
						$countries[ $onboarding['country'] ]
					),
					'category_id' => 1,
					'type'        => \PersonalizeWP_Rule_Types::$CUSTOM, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					'created_by'  => get_current_user_id(),
				];
				$conditions = array(
					(object) array(
						'measure'    => 'core_visitor_country',
						'comparator' => 'equals',
						'value'      => $onboarding['country'],
					),
				);
				// Conditions needs to be encoded to match the expected DB format.
				$rule_data['conditions_json'] = wp_json_encode( $conditions );

				$rule = new \PersonalizeWP_Rule( $rule_data );
				$rule->save();
			}
		}

		return true;
	}

	/**
	 * Process the form that is part of this step, verifying data submitted.
	 *
	 * @param array $form_data Pre-sanitized form data
	 *
	 * @return bool|\WP_Error True on success, WP_Error otherwise
	 */
	public function process( $form_data ) {

		$processed_data = [];
		// Validate data to process
		foreach ( $form_data as $key => $value ) {
			switch ( $key ) {
				case 'site_description':
					if ( in_array( $value, $this->description_opts, true ) ) {
						$processed_data[ $key ] = $value;
					}
					break;

				case 'country':
					$countries = \personalizewp()->get_countries();
					if ( in_array( $value, array_keys( $countries ), true ) ) {
						$processed_data[ $key ] = $value;
					}
					break;

				case 'sell':
					if ( in_array( $value, [ 'no', 'yes' ], true ) ) {
						$processed_data[ $key ] = $value;
					}
					break;
			}
		}
		// Store the processed data
		$this->store_data( $processed_data );

		return true;
	}

	/**
	 * Display the main content for the step
	 *
	 * @return void
	 */
	public function display() {

		$current_data = get_option( 'pwp_onboarding' );

		// Using class `interstitial` to submit the form upon submission of "next step"
		// All interstitial forms have the same `wp_ajax_pwp_onboarding` action
		// But the dynamically added step num triggers the processing of the form above.
		?>
		<form method="post" action="" data-type="interstitial" class="pwp-form flow">
			<input type="hidden" name="action" value="pwp_onboarding_wizard" />
			<?php wp_nonce_field( 'pwp-onboarding-wizard', '_ajax_nonce' ); ?>

				<label for="pwp_site_description"><?php esc_html_e( 'How would you best describe your website?', 'personalizewp' ); ?></label>

				<select id="pwp_site_description" name="pwp_onboarding[site_description]">
					<option value=""><?php esc_html_e( '-- Select an option --', 'personalizewp' ); ?></option>
					<?php
					foreach ( $this->description_opts as $option ) :
						printf(
							'<option value="%1$s" %3$s>%2$s</option>',
							esc_attr( $option ),
							esc_html( $option ),
							selected( $option, ( $current_data['site_description'] ?? '' ), false )
						);
					endforeach;
					?>
				</select>

				<label for="pwp_country"><?php esc_html_e( 'Where are you based?', 'personalizewp' ); ?></label>

				<select id="pwp_country" name="pwp_onboarding[country]">
					<option value=""><?php esc_html_e( '-- Select a country --', 'personalizewp' ); ?></option>
					<?php
					$countries = \personalizewp()->get_countries();
					foreach ( $countries as $iso_code => $country_name ) :
						printf(
							'<option value="%1$s" %3$s>%2$s</option>',
							esc_attr( $iso_code ),
							esc_html( $country_name ),
							selected( $iso_code, ( $current_data['country'] ?? '' ), false )
						);
					endforeach;
					?>
				</select>

				<p class="label"><?php esc_html_e( 'Do you sell products or services?', 'personalizewp' ); ?></p>

				<div class="cluster checkmarks">
					<label for="pwp-sell-no" class="checkmark">
						<input id="pwp-sell-no" type="radio" name="pwp_onboarding[sell]" value="no" <?php checked( 'no', ( $current_data['sell'] ?? '' ) ); ?> />
						<?php esc_html_e( 'No', 'personalizewp' ); ?>
						<?php Utils::svg_icon( 'check' ); ?>
					</label>

					<label for="pwp-sell-yes" class="checkmark">
						<input id="pwp-sell-yes" type="radio" name="pwp_onboarding[sell]" value="yes" <?php checked( 'yes', ( $current_data['sell'] ?? '' ) ); ?> />
						<?php esc_html_e( 'Yes', 'personalizewp' ); ?>
						<?php Utils::svg_icon( 'check' ); ?>
					</label>

				</div>

		</form>
		<?php
	}
}
