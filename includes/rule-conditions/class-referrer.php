<?php
/**
 * Referrer type condition Rules
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
 * Checks for the existence of Referrer based conditions
 */
class Referrer extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'core_referrer';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Referrer', 'personalizewp' );

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
	 * @param string $value      Referrer to check
	 * @param string $action     Action to take
	 * @param object $meta       Additional meta data, unused
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
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @uses Global 'PERSONALIZEWP_PARAMS'
	 *
	 * @param string $value Referrer to check
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value ) {
		if ( $GLOBALS['PERSONALIZEWP_PARAMS']['referrer_url'] === $value ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Does not equal" test
	 *
	 * @uses Global 'PERSONALIZEWP_PARAMS'
	 *
	 * @param string $value Referrer to check
	 *
	 * @return bool
	 */
	public function comparatorDoesNotEqual( $value ) {
		if ( $GLOBALS['PERSONALIZEWP_PARAMS']['referrer_url'] !== $value ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Contains" test
	 *
	 * @uses Global 'PERSONALIZEWP_PARAMS'
	 *
	 * @param string $value Referrer to check
	 *
	 * @return bool
	 */
	public function comparatorContains( $value ) {
		return ( str_contains( $GLOBALS['PERSONALIZEWP_PARAMS']['referrer_url'], $value ) );
	}

	/**
	 * "Does not contain" test
	 *
	 * @uses Global 'PERSONALIZEWP_PARAMS'
	 *
	 * @param string $value Referrer to check
	 *
	 * @return bool
	 */
	public function comparatorDoesNotContain( $value ) {
		return ( ! str_contains( $GLOBALS['PERSONALIZEWP_PARAMS']['referrer_url'], $value ) );
	}
}
