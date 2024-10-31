<?php
/**
 * WooCommerce purchase type condition Rules
 *
 * @link       https://personalizewp.com
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

use PersonalizeWP\Rule_Conditions\RuleCondition;
use PersonalizeWP\Rule_Conditions\Dependency\Woocommerce;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks for the existance of completed WooCommerce order conditions
 *
 * Requires the Visitor Profile to have recorded a WC order, though will allow
 * checking if the user is logged in to their WP/WooCommerce user account.
 */
class WooCommerce_Completed_Purchase extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'woocommerce_completed_purchase';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'WooCommerce', 'personalizewp' );

		$this->description = __( 'Visitor Has Completed Purchase', 'personalizewp' );

		$this->comparators = [
			'equals' => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [
			'true'  => _x( 'True', 'Comparison value', 'personalizewp' ),
			'false' => _x( 'False', 'Comparison value', 'personalizewp' ),
		];
	}

	/**
	 * Test data against condition
	 *
	 * @param string $comparator Comparator test to run
	 * @param string $value      Comparison to check
	 * @param string $action     Action to take, unused
	 * @param object $meta       Additional meta data, unused
	 *
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {
		switch ( $comparator ) {
			case 'equals':
				return $this->comparatorEquals( $value );
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @param string $value Condition value to check against, true or false
	 *
	 * @return bool True if the current visitor matches the condition value, false otherwise
	 */
	public function comparatorEquals( $value ) : bool {

		// Convert to bool
		$value = (bool) ( 'true' === $value );

		// Check for direct WP user
		if ( ! is_user_logged_in() ) {
			// Return based on value, if condition was set to false this will return true.
			return false === $value;
		}

		// Use WP User ID, fallback to original logic
		$order_count      = wc_get_customer_order_count( get_current_user_id() );
		$possible_puchase = 0 < $order_count;

		// Match possible purchase against condition comparator value
		return $value === (bool) $possible_puchase;
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
