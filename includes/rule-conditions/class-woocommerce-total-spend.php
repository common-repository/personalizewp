<?php
/**
 * WooCommerce total spend type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

use PersonalizeWP\Rule_Conditions\Dependency\Woocommerce;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks WooCommerce orders for total spend conditions
 *
 * Requires the user to be logged into their WP/WooCommerce user account.
 */
class WooCommerce_Total_Spend extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'woocommerce_total_spend';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'WooCommerce', 'personalizewp' );

		$this->description = __( 'Total Spend', 'personalizewp' );

		$this->comparators = [
			'more_than' => _x( 'More Than', 'Comparator', 'personalizewp' ),
			'less_than' => _x( 'Less Than', 'Comparator', 'personalizewp' ),
			'equals'    => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [];

		$this->comparison_type = 'text';
	}

	/**
	 * Test spend against condition
	 *
	 * @param string $comparator Comparator test to run
	 * @param string $value      Total spend to compare against
	 * @param string $action     Action to take, unused
	 * @param object $meta       Additional meta data, unused
	 *
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {

		// Check for direct WP user, i.e. a WooCommerce account
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Note: Dealing with floats is always problematic due to PHP interal representation.
		// So multiple by 100 removing any decimal (pence), i.e. convert everything to an int.
		$value_condition = is_numeric( $value ) ? absint( 100 * $value ) : 0;
		$total_spend     = absint( 100 * wc_get_customer_total_spent( get_current_user_id() ) );

		// Ensure we have values to use, not zero numbers.
		if ( empty( $total_spend ) || empty( $value_condition ) ) {
			return false;
		}

		switch ( $comparator ) {
			case 'more_than':
				return $total_spend >= $value_condition;

			case 'less_than':
				return $total_spend <= $value_condition;

			case 'equals':
				return $total_spend === $value_condition;
		}

		return false;
	}

	/**
	 * Array of dependencies for the condition to be used
	 *
	 * @return array
	 */
	public function dependencies() : array {
		return [
			Woocommerce::instance(),
		];
	}
}
