<?php
/**
 * Checks for the existence of WooCommerce
 *
 * @link       https://personalizewp.com
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions/Dependancy
 */

namespace PersonalizeWP\Rule_Conditions\Dependency;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * WooCommerce dependancy checker
 */
class Woocommerce extends RuleDependency {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->description = sprintf(
			/* translators: 1: %s plugin name. */
			__( '%s must be installed and activated', 'personalizewp' ),
			'WooCommerce'
		);
		$this->success_message = sprintf(
			/* translators: 1: %s plugin name. */
			__( '%s is installed and activated', 'personalizewp' ),
			'WooCommerce'
		);
		$this->failure_message = sprintf(
			/* translators: 1: %s plugin name. */
			__( '%s is either not installed or not activated', 'personalizewp' ),
			'WooCommerce'
		);
	}

	/**
	 * Helper function to retrieve product information from the shopping cart.
	 *
	 * @since 2.5.0
	 *
	 * @return array Returns array of product information.
	 */
	public function get_product_information_from_cart() {

		$products = [];

		if ( ! $this->initialise_session_cart() ) {
			return $products;
		}

		$cart = \WC()->cart->get_cart();

		if ( ! empty( $cart ) && is_array( $cart ) ) {
			foreach ( $cart as $item ) {
				$id       = isset( $item['product_id'] ) ? $item['product_id'] : '';
				$var_id   = isset( $item['variation_id'] ) ? $item['variation_id'] : '';
				$quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
				$total    = isset( $item['line_total'] ) ? (float) $item['line_total'] : 0;
				$cats     = \wc_get_product_term_ids( $id, 'product_cat' );

				$products = $this->process_product_data( $products, $id, $var_id, $quantity, $total, $cats );
			}
		}

		return $products;
	}

	/**
	 * Validates that WooCommerce is around and initialises all things to get access to the session/cart to handle our logic
	 *
	 * @since 2.5.0
	 *
	 * @return boolean $initialised True if initialised, false otherwise.
	 */
	private function initialise_session_cart() : bool {

		$initialised = false;

		if ( ! $this->verify() ) {
			return $initialised;
		}

		// Which in turns initialises WC, the session, and the cart.
		\WC()->init();

		// Should be empty at least
		$initialised = isset( \WC()->cart );

		return $initialised;
	}

	/**
	 * Helper function to reformat product data into a usable array.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $products Array of products.
	 * @param string $id       The product id.
	 * @param string $var_id   The product variation id if it exits.
	 * @param int    $quantity The qualtity of the product.
	 * @param int    $total    The total value of the product.
	 * @param array  $cats     The categories assigned to the product.
	 * @return array           Returns array of product information.
	 */
	private function process_product_data( $products, $id, $var_id, $quantity, $total, $cats ) {

		if ( $id ) {
			if ( $id && array_key_exists( $id, $products ) ) {
				$products[ $id ]['quantity'] = $products[ $id ]['quantity'] + $quantity;
				$products[ $id ]['total']    = $products[ $id ]['total'] + $total;
			} else {
				$products[ $id ] = array(
					'quantity' => $quantity,
					'total'    => $total,
				);
			}

			$products[ $id ]['categories'] = $cats;

			// Add an entry for the variable price, if it exists.
			if ( $var_id ) {

				// Append the variable price id to the product id.
				$var_price_id = $id . '_' . $var_id;

				if ( array_key_exists( $var_price_id, $products ) ) {
					$products[ $var_price_id ]['quantity'] = $products[ $var_price_id ]['quantity'] + $quantity;
					$products[ $var_price_id ]['total']    = $products[ $var_price_id ]['total'] + $total;
				} else {
					$products[ $var_price_id ] = array(
						'quantity' => $quantity,
						'total'    => $total,
					);
				}

				$products[ $var_price_id ]['categories'] = $cats;
			}
		}

		return $products;
	}

	/**
	 * Execute test to check if dependency is met
	 *
	 * @return bool
	 */
	public function verify() : bool {
		return function_exists( '\WC' ) && class_exists( '\WooCommerce' );
	}
}
