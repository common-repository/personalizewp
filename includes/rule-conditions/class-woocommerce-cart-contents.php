<?php
/**
 * WooCommerce cart contents type condition Rules
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
 * Checks for the existance of WooCommerce cart contents conditions
 *
 * Uses the WC Cart functionality which uses sessions and cookies.
 */
class WooCommerce_Cart_Contents extends RuleCondition {

	/**
	 * Container of WC dependancy and helper functions
	 *
	 * @var Woocommerce
	 */
	private object $woocommerce;

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'woocommerce_cart_contents';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'WooCommerce', 'personalizewp' );

		$this->description = __( 'Cart Contents', 'personalizewp' );

		$this->comparators = [
			// 'empty' => _x( 'Is Empty', 'Comparator', 'personalizewp' ), // Future use, when we remove values and allow specific Products/cats
			'notEmpty' => _x( 'Has Products', 'Comparator', 'personalizewp' ),
		];

		// Ideally remove these and have the above separate comparators, but not possible with current UI.
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

		// Future compatibility to allow value removals
		if ( 'false' === $value && 'empty' === $comparator ) {
			$comparator = 'notEmpty';
		}

		$cart_products = $this->woocommerce->get_product_information_from_cart();

		switch ( $comparator ) {
			case 'empty':
				return 0 === count( $cart_products );

			case 'notEmpty':
				return 0 < count( $cart_products );
		}

		return false;
	}

	/**
	 * Array of dependencies for the condition to be used
	 *
	 * @return array
	 */
	public function dependencies() : array {

		$this->woocommerce = Woocommerce::instance();

		return [
			$this->woocommerce,
		];
	}
}
