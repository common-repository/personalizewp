<?php
/**
 * User last visited type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks if a visitor has visited more/less than a number of days ago.
 */
class Users_Last_Visit extends RuleCondition {

	public string $identifier = 'core_users_last_visit';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Last Visit', 'personalizewp' );

		$this->comparators = [
			'more_than' => _x( 'More Than', 'Comparator', 'personalizewp' ),
			'less_than' => _x( 'Less Than', 'Comparator', 'personalizewp' ),
			'equals'    => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		// Create an array of keys with empty values.
		$this->comparison_values = array_fill_keys( range( 1, 30 ), '' );
		// Loop over array and modify values to set translatable string.
		array_walk(
			$this->comparison_values,
			function ( &$item, $key ) {
				/* translators: 1: %d number of days. */
				$item = sprintf( _n( '%d Day ago', '%d Days ago', $key, 'personalizewp' ), $key );
			}
		);
	}

	/**
	 * Test data against condition
	 *
	 * @param  string $comparator
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {
		switch ( $comparator ) {
			case 'more_than':
				return $this->comparatorMoreThan( $value );
			case 'less_than':
				return $this->comparatorLessThan( $value );
			case 'equals':
				return $this->comparatorEquals( $value );
		}

		return false;
	}

	/**
	 * "More than" test
	 *
	 * @return bool
	 */
	public function comparatorMoreThan( $value ) {
		if ( $GLOBALS['PERSONALIZEWP_PARAMS']['daysSinceLastVisit'] > $value ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Less than" test
	 *
	 * @return bool
	 */
	public function comparatorLessThan( $value ) {
		if ( $GLOBALS['PERSONALIZEWP_PARAMS']['daysSinceLastVisit'] < $value ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Equal" test
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value ) {
		if ( $GLOBALS['PERSONALIZEWP_PARAMS']['daysSinceLastVisit'] == $value ) {
			return true;
		} else {
			return false;
		}
	}
}
