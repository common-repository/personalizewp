<?php
/**
 * UTM Tag type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      2.6.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions/UTM
 */

namespace PersonalizeWP\Rule_Conditions\UTM;

use PersonalizeWP\Rule_Conditions\RuleCondition;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks for the existence of URL Query string and Fragment string based conditions
 */
abstract class Tag extends RuleCondition {

	/**
	 * UTM Tag to look for
	 *
	 * @var string
	 */
	protected string $utm_tag = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'UTM Tags', 'personalizewp' );

		$this->comparators = [
			'equals'           => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
			'does_not_equal'   => _x( 'Does Not Equal', 'Comparator', 'personalizewp' ),
			'contains'         => _x( 'Contains', 'Comparator', 'personalizewp' ),
			'does_not_contain' => _x( 'Does Not Contain', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [];

		$this->comparison_type = 'text';
	}

	/**
	 * Test data against condition
	 *
	 * @param string $comparator Comparator test to run
	 * @param string $value      Comparison to check
	 * @param string $action     Action to take
	 * @param object $meta       Additional meta data, URL param
	 *
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {
		switch ( $comparator ) {
			case 'equals':
				return $this->comparator_equals( $value );
			case 'does_not_equal':
				return $this->comparator_does_not_equal( $value );
			case 'contains':
				return $this->comparator_contains( $value );
			case 'does_not_contain':
				return $this->comparator_does_not_contain( $value );
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @param string $value Value of URL to test for
	 *
	 * @return bool
	 */
	protected function comparator_equals( $value ) {
		$param_val = $this->get_url_query_param();

		if ( $param_val === $value ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Does not equal" test
	 *
	 * @param string $value Value of URL to test for
	 *
	 * @return bool
	 */
	protected function comparator_does_not_equal( $value ) {
		$param_val = $this->get_url_query_param();

		if ( $param_val !== $value ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Contains" test
	 *
	 * @param string $value Value of URL to test for
	 *
	 * @return bool
	 */
	public function comparator_contains( $value ) {
		$param_val = $this->get_url_query_param();

		// Cannot match if empty.
		if ( empty( $param_val ) ) {
			return false;
		}

		return ( str_contains( $param_val, $value ) );
	}

	/**
	 * "Does not contain" test
	 *
	 * @param string $value Value of URL to test for
	 *
	 * @return bool
	 */
	public function comparator_does_not_contain( $value ) {
		$param_val = $this->get_url_query_param();

		// If URL query is empty, clearly it can not contain the test value.
		if ( empty( $param_val ) ) {
			return true;
		}

		return ( ! str_contains( $param_val, $value ) );
	}

	/**
	 * Return the value of the specified URL query parameter
	 *
	 * @uses Global 'PERSONALIZEWP_PARAMS'
	 *
	 * @return string|false String if set, false otherwise
	 */
	protected function get_url_query_param() {
		$url_string = $GLOBALS['PERSONALIZEWP_PARAMS']['url_query_string'];

		// Cannot match if empty, or no tag to look for.
		if ( empty( $url_string ) || empty( $this->utm_tag ) ) {
			return false;
		}

		// URL is lower cased for ease.
		$url_string = strtolower( $url_string );
		$queries    = wp_parse_url( $url_string, PHP_URL_QUERY );
		$fragments  = wp_parse_url( $url_string, PHP_URL_FRAGMENT );

		$query_params    = [];
		$fragment_params = [];
		// Parse query and possible fragment args into arrays for easier processing.
		if ( ! empty( $queries ) ) {
			parse_str( $queries, $query_params );
		}
		if ( ! empty( $fragments ) ) {
			parse_str( $fragments, $fragment_params );
		}

		// Traditional URL params take priority
		if ( ! empty( $query_params ) && isset( $query_params[ $this->utm_tag ] ) ) {
			return $query_params[ $this->utm_tag ];
		}
		// Then #hash style params.
		if ( ! empty( $fragment_params ) && isset( $fragment_params[ $this->utm_tag ] ) ) {
			return $fragment_params[ $this->utm_tag ];
		}

		return false;
	}
}
