<?php
/**
 * New visitor type condition Rules
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
 * Checks if a visitor is regarded as new or not
 *
 * Uses a JS local storage variable and within one day of the initial visit is regarded as new.
 */
class New_Visitor extends RuleCondition {

	public string $identifier = 'core_new_visitor';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'New Visit', 'personalizewp' );

		$this->comparators = [
			'equals' => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
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

		return $value == 'false' ? $GLOBALS['PERSONALIZEWP_PARAMS']['returning_visitor'] : ! $GLOBALS['PERSONALIZEWP_PARAMS']['returning_visitor'];
	}
}
