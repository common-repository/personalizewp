<?php
/**
 * WooCommerce total products purchased type condition Rules
 *
 * @link       https://personalizewp.com
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

use PersonalizeWP\Rule_Conditions\Dependency\Woocommerce;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks WooCommerce orders for total products purchased conditions
 *
 * Requires the user to be logged into their WP/WooCommerce user account.
 */
class WooCommerce_Total_Products_Purchased extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'woocommerce_total_products_purchased';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'WooCommerce', 'personalizewp' );

		$this->description = __( 'Total Products Purchased', 'personalizewp' );

		$this->comparators = [
			'more_than' => _x( 'More Than', 'Comparator', 'personalizewp' ),
			'less_than' => _x( 'Less Than', 'Comparator', 'personalizewp' ),
			'equals'    => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [];

		$this->comparison_type = 'text';
	}

	/**
	 * Test total products against condition
	 *
	 * Note: WooCommerce doesn't have a get total items across all orders, only on each order.
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

		$value_condition = is_numeric( $value ) ? absint( $value ) : 0;
		// Ensure we have values to use, not zero numbers.
		if ( empty( $value_condition ) ) {
			return false;
		}

		// Get orders by customer ID.
		$orders = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'status'      => array( 'wc-completed' ),
			)
		);
		if ( empty( $orders ) ) {
			return false;
		}

		$total_quantity = 0;
		foreach ( $orders as $order ) {
			$total_quantity += absint( $order->get_item_count() );

			// Check condition after each count for early return
			if ( $total_quantity >= $value_condition ) {
				switch ( $comparator ) {
					case 'more_than':
						// Already bought more than the check, can't become more true.
						return true;

					case 'less_than':
						// Already bought more than the check, can't become more false.
						return false;
				}
			}
		} // Loop all orders

		switch ( $comparator ) {
			case 'more_than':
				return $total_quantity >= $value_condition;

			case 'less_than':
				return $total_quantity <= $value_condition;

			case 'equals':
				return $total_quantity === $value_condition;
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
