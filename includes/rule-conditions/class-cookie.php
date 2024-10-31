<?php
/**
 * Cookie contents type condition Rules
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
 * Checks for the existence of Cookie based conditions
 */
class Cookie extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'core_cookie';

	/**
	 * Measure used to adjust the admin form
	 *
	 * @var string
	 */
	public string $measure_key = 'cookie_name';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Cookie', 'personalizewp' );

		$this->comparators = [
			'any_value'        => _x( 'Has any Value (Exists)', 'Comparator', 'personalizewp' ),
			'no_value'         => _x( 'Has no Value (Does not exist)', 'Comparator', 'personalizewp' ),
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
	 * @param object $meta       Additional meta data, cookie name/key
	 *
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {
		switch ( $comparator ) {
			case 'any_value':
				return $this->comparatorAnyValue( $value, $meta->cookie_name );
			case 'no_value':
				return $this->comparatorNoValue( $value, $meta->cookie_name );
			case 'equals':
				return $this->comparatorEquals( $value, $meta->cookie_name );
			case 'does_not_equal':
				return $this->comparatorDoesNotEqual( $value, $meta->cookie_name );
			case 'contains':
				return $this->comparatorContains( $value, $meta->cookie_name );
			case 'does_not_contain':
				return $this->comparatorDoesNotContain( $value, $meta->cookie_name );
		}

		return false;
	}

	/**
	 * "Any value" and exists test
	 * Cookie name is sanitized before testing
	 *
	 * @param string $value      Value of cookie to test for, unused
	 * @param string $cookiename Name of cookie to test for
	 *
	 * @return bool
	 */
	public function comparatorAnyValue( $value, $cookiename ) {
		$cookiename = sanitize_text_field( $cookiename );
		if ( isset( $_COOKIE[ $cookiename ] ) && ! empty( $_COOKIE[ $cookiename ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "No value" or doesn't exist test
	 * Cookie name is sanitized before testing
	 *
	 * @param string $value      Value of cookie to test for, unused
	 * @param string $cookiename Name of cookie to test for
	 *
	 * @return bool
	 */
	public function comparatorNoValue( $value, $cookiename ) {
		$cookiename = sanitize_text_field( $cookiename );
		if ( ! isset( $_COOKIE[ $cookiename ] ) || isset( $_COOKIE[ $cookiename ] ) && empty( $_COOKIE[ $cookiename ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Equal" value test
	 * Cookie name/value is sanitized before testing
	 *
	 * @param string $value      Value of cookie to test for
	 * @param string $cookiename Name of cookie to test for
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value, $cookiename ) {
		$cookiename  = sanitize_text_field( $cookiename );
		$cookievalue = isset( $_COOKIE[ $cookiename ] ) ? sanitize_text_field( $_COOKIE[ $cookiename ] ) : '';
		return $cookievalue === $value;
	}

	/**
	 * "Does not equal" test
	 * Cookie name/value is sanitized before testing
	 *
	 * @param string $value      Value of cookie to test for
	 * @param string $cookiename Name of cookie to test for
	 *
	 * @return bool
	 */
	public function comparatorDoesNotEqual( $value, $cookiename ) {
		$cookiename  = sanitize_text_field( $cookiename );
		$cookievalue = isset( $_COOKIE[ $cookiename ] ) ? sanitize_text_field( $_COOKIE[ $cookiename ] ) : '';
		return $cookievalue !== $value;
	}

	/**
	 * "Contains" test
	 * Cookie name/value is sanitized before testing
	 *
	 * @param string $value      Value of cookie to test for
	 * @param string $cookiename Name of cookie to test for
	 *
	 * @return bool
	 */
	public function comparatorContains( $value, $cookiename ) {
		$cookiename  = sanitize_text_field( $cookiename );
		$cookievalue = isset( $_COOKIE[ $cookiename ] ) ? sanitize_text_field( $_COOKIE[ $cookiename ] ) : '';
		return str_contains( $cookievalue, $value );
	}

	/**
	 * "Does not contain" test
	 * Cookie name/value is sanitized before testing
	 *
	 * @param string $value      Value of cookie to test for
	 * @param string $cookiename Name of cookie to test for
	 *
	 * @return bool
	 */
	public function comparatorDoesNotContain( $value, $cookiename ) {
		$cookiename  = sanitize_text_field( $cookiename );
		$cookievalue = isset( $_COOKIE[ $cookiename ] ) ? sanitize_text_field( $_COOKIE[ $cookiename ] ) : '';
		return ! str_contains( $cookievalue, $value );
	}
}
