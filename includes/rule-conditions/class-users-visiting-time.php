<?php
/**
 * Generic Visiting Time type condition Rules
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
 * Checks for generic visiting time rule conditions
 */
class Users_Visiting_Time extends RuleCondition {

	public string $identifier = 'core_users_visiting_time';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Visit Period', 'personalizewp' );

		$this->comparators = [
			'equals' => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [
			'morning'   => _x( 'Morning', 'Comparison value', 'personalizewp' ),
			'afternoon' => _x( 'Afternoon', 'Comparison value', 'personalizewp' ),
			'evening'   => _x( 'Evening', 'Comparison value', 'personalizewp' ),
			'nighttime' => _x( 'Nighttime', 'Comparison value', 'personalizewp' ),
		];
	}

	/**
	 * Test data against condition
	 *
	 * @param  string $comparator
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
	 * @return bool
	 */
	public function comparatorEquals( $value ) {
		if ( $GLOBALS['PERSONALIZEWP_PARAMS']['time_of_day'] == $value ) {
			return true;
		} else {
			return false;
		}
	}
}
