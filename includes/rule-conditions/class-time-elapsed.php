<?php
/**
 * Elapsed Time type condition Rules
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
 * Triggers Time Elapsed conditions
 *
 * Attaches extra atributes to the FE placeholder for the JS to show/hide the element after an amount of time
 */
class Time_Elapsed extends RuleCondition {

	public string $identifier = 'core_time_elapsed';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'Time on Current Page', 'personalizewp' );

		$this->comparators = [
			'equals' => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
		];

		$now = time();
		// Create an array of keys as set number of seconds with empty values.
		$this->comparison_values = array_fill_keys( [ 5, 10, 15, 30, 60, 120, 180, 240, 300, 600, 900, 1200, 1800, 2700, 3600 ], '' );
		// Loop over array and modify values to set translatable string.
		array_walk(
			$this->comparison_values,
			function ( &$item, $key ) use ( $now ) {
				// Use WP to set the secs/mins/hours/days.
				$item = human_time_diff( $now, $now + $key );
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
			case 'equals':
				return $this->comparatorEquals( $value, $action );
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value, $action ) : bool {
		return $action === 'show';
	}

	/**
	 * Array of tag attributes to be included in WP DXP tag given the chosen condition
	 *
	 * @param  StdClass $condition
	 * @param  string   $action
	 * @return array
	 */
	public function tagAttributes( $condition, $action ) : array {
		return [ ( $action === 'show' ? 'delayed' : 'lifetime' ) => $condition->value ];
	}
}
