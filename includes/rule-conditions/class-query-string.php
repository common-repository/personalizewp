<?php
/**
 * Query String type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks for the existence of URL Query string and Fragment string based conditions
 */
class Query_String extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'core_query_string';

	/**
	 * Measure used to adjust the admin form
	 *
	 * @var string
	 */
	public string $measure_key = 'key_name';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Query String', 'personalizewp' );

		$this->comparators = [
			'equals'           => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
			'does_not_equal'   => _x( 'Does Not Equal', 'Comparator', 'personalizewp' ),
			'contains'         => _x( 'Contains', 'Comparator', 'personalizewp' ),
			'does_not_contain' => _x( 'Does Not Contain', 'Comparator', 'personalizewp' ),
			'key_value'        => _x( 'Key/Value', 'Comparator', 'personalizewp' ),
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
				return $this->comparatorEquals( $value );
			case 'does_not_equal':
				return $this->comparatorDoesNotEqual( $value );
			case 'contains':
				return $this->comparatorContains( $value );
			case 'does_not_contain':
				return $this->comparatorDoesNotContain( $value );
			case 'key_value':
				return $this->comparatorKeyValue( $value, $meta->key_name );
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
	public function comparatorEquals( $value ) {
		$the_query_string = $this->getURLQueryString();

		if ( $the_query_string === $value ) {
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
	public function comparatorDoesNotEqual( $value ) {

		$the_query_string = $this->getURLQueryString();

		if ( $the_query_string !== $value ) {
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
	public function comparatorContains( $value ) {
		$the_query_string = $this->getURLQueryString();

		return ( str_contains( $the_query_string, $value ) );
	}

	/**
	 * "Does not contain" test
	 *
	 * @param string $value Value of URL to test for
	 *
	 * @return bool
	 */
	public function comparatorDoesNotContain( $value ) {
		$the_query_string = $this->getURLQueryString();

		return ( ! str_contains( $the_query_string, $value ) );
	}

	/**
	 * "Key/Value" test
	 *
	 * @param string $value Value of URL param to test for
	 * @param string $key   Name of URL param to test for
	 *
	 * @return bool
	 */
	public function comparatorKeyValue( $value, $key ) {
		$url_string = $this->getURLQueryString();

		// Cannot match if empty.
		if ( empty( $url_string ) ) {
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

		if ( ! empty( $query_params ) && isset( $query_params[ $key ] ) && $query_params[ $key ] === $value ) {
			return true;
		}
		if ( ! empty( $fragment_params ) && isset( $fragment_params[ $key ] ) && $fragment_params[ $key ] === $value ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the current query string from the url
	 *
	 * @uses Global 'PERSONALIZEWP_PARAMS'
	 *
	 * @return string
	 */
	public function getURLQueryString() {
		return $GLOBALS['PERSONALIZEWP_PARAMS']['url_query_string'];
	}
}
