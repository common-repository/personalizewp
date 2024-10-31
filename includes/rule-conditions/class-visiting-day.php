<?php
/**
 * Day of week type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      2.4.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks against the current day of week conditions
 */
class Visiting_Day extends RuleCondition {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'core_visiting_day';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Day of Week', 'personalizewp' );

		$this->comparators = [
			'equals'         => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
			'does_not_equal' => _x( 'Does Not Equal', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = [
			'monday'    => _x( 'Monday', 'Comparison value', 'personalizewp' ),
			'tuesday'   => _x( 'Tuesday', 'Comparison value', 'personalizewp' ),
			'wednesday' => _x( 'Wednesday', 'Comparison value', 'personalizewp' ),
			'thursday'  => _x( 'Thursday', 'Comparison value', 'personalizewp' ),
			'friday'    => _x( 'Friday', 'Comparison value', 'personalizewp' ),
			'saturday'  => _x( 'Saturday', 'Comparison value', 'personalizewp' ),
			'sunday'    => _x( 'Sunday', 'Comparison value', 'personalizewp' ),
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

		$value = strtolower( $value );
		// Validate rule data from DB
		if ( ! in_array( $value, array_keys( $this->comparison_values ), true ) ) {
			return false;
		}

		$day_of_week = $this->get_day_of_week();
		if ( empty( $day_of_week ) ) {
			return false;
		}

		switch ( $comparator ) {
			case 'equals':
				return $day_of_week === $value;

			case 'does_not_equal':
				return $day_of_week !== $value;
		}

		return false;
	}

	/**
	 * Return the day of the week from Visitors JS timestamp
	 *
	 * @return string|false Day of week or false if invalid
	 */
	private function get_day_of_week() {

		// Use Visitors POV when resolving the current day of the week.
		$timestamp = ! empty( $GLOBALS['PERSONALIZEWP_PARAMS']['users_current_timestamp'] ) ? strtotime( $GLOBALS['PERSONALIZEWP_PARAMS']['users_current_timestamp'] ) : false;
		if ( empty( $timestamp ) ) {
			return false;
		}

		// Validate day of week
		$day_of_week = strtolower( gmdate( 'l', $timestamp ) );
		if ( ! in_array( $day_of_week, array_keys( $this->comparison_values ), true ) ) {
			return false;
		}

		return $day_of_week;
	}
}
