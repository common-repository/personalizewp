<?php
/**
 * Specific Day, before/after type condition Rules
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
 * Checks the current date against specific date conditions
 */
class Visiting_Date extends RuleCondition {

	public string $identifier = 'core_visiting_date';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Date', 'personalizewp' );

		$this->comparators = [
			'before' => _x( 'Before', 'Comparator', 'personalizewp' ),
			'after'  => _x( 'After', 'Comparator', 'personalizewp' ),
			'equals' => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [];

		$this->comparison_type = 'datepicker';
	}

	/**
	 * Test data against condition
	 *
	 * @param  string $comparator
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {
		switch ( $comparator ) {
			case 'before':
				return $this->comparatorBefore( $value );
			case 'after':
				return $this->comparatorAfter( $value );
			case 'equals':
				return $this->comparatorEquals( $value );
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value ) {
		$ruleStrToTime  = gmdate( 'Y-m-d', strtotime( $value ) );
		$usersStrToTime = gmdate( 'Y-m-d', strtotime( $GLOBALS['PERSONALIZEWP_PARAMS']['users_current_timestamp'] ) );

		if ( $ruleStrToTime === $usersStrToTime ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Before" test
	 *
	 * @return bool
	 */
	public function comparatorBefore( $value ) {

		$ruleStrToTime  = gmdate( 'Y-m-d', strtotime( $value ) );
		$usersStrToTime = gmdate( 'Y-m-d', strtotime( $GLOBALS['PERSONALIZEWP_PARAMS']['users_current_timestamp'] ) );

		if ( $ruleStrToTime > $usersStrToTime ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "After" test
	 *
	 * @return bool
	 */
	public function comparatorAfter( $value ) {

		$ruleStrToTime  = gmdate( 'Y-m-d', strtotime( $value ) );
		$usersStrToTime = gmdate( 'Y-m-d', strtotime( $GLOBALS['PERSONALIZEWP_PARAMS']['users_current_timestamp'] ) );

		if ( $ruleStrToTime < $usersStrToTime ) {
			return true;
		} else {
			return false;
		}
	}
}
