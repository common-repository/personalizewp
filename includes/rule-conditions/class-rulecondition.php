<?php
/**
 * PersonalizeWP
 *
 * @link       https://personalizewp.com/
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * All Rule Conditions extend this abstract.
 */
abstract class RuleCondition {

	use SingletonTrait;

	public string $category = '';

	public string $description = '';

	public string $measure_key = '';

	/**
	 * Standard comparators for a condition
	 *
	 * @var array
	 */
	protected $comparators;

	/**
	 * Potential values used for a comparison
	 *
	 * @var array
	 */
	protected $comparison_values;

	/**
	 * Potential values used for a comparison Type
	 *
	 * @var string
	 */
	protected $comparison_type = 'select'; // select / text / datepicker

	/**
	 * Condition identifier - must be unique
	 *
	 * @var string
	 */
	public string $identifier;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->comparators       = [
			'equals'         => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
			'does_not_equal' => _x( 'Does Not Equal', 'Comparator', 'personalizewp' ),
			'any'            => _x( 'Is any of', 'Comparator', 'personalizewp' ),
		];
		$this->comparison_values = [
			'true'  => _x( 'True', 'Comparison value', 'personalizewp' ),
			'false' => _x( 'False', 'Comparison value', 'personalizewp' ),
		];
	}

	/**
	 * Test data against condition
	 *
	 * @param string $comparator Comparator test to run
	 * @param mixed  $value      Comparison to check
	 * @param string $action     Action to take
	 * @param array  $meta       Additional meta data
	 *
	 * @return bool
	 */
	abstract public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool;

	/**
	 * Retrieve potential comparator values
	 *
	 * @return array
	 */
	public function getComparators() : array {
		return $this->comparators;
	}

	/**
	 * Retrieve potential comparison values
	 *
	 * @return array
	 */
	public function getComparisonValues() : array {
		return $this->comparison_values;
	}

	/**
	 * Retrieve potential comparison types
	 *
	 * @return string
	 */
	public function getComparisonType() : string {
		return $this->comparison_type;
	}

	/**
	 * Convert to object (usually for json on page)
	 *
	 * @return stdClass
	 */
	public function toObject() {
		return (object) [
			'identifier'       => $this->identifier,
			'comparators'      => $this->comparators,
			'measureKey'       => $this->measure_key,
			'comparisonValues' => $this->getComparisonValues(),
			'comparisonType'   => $this->getComparisonType(),
		];
	}

	/**
	 * Check that the dependencies are met for the condition to be used
	 *
	 * @return bool
	 */
	public function isUsable() : bool {
		foreach ( $this->dependencies() as $dependency ) {
			if ( ! $dependency->verify() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Array of dependencies for the condition to be used
	 *
	 * @return array
	 */
	public function dependencies() : array {
		return [];
	}

	/**
	 * Array of tag attributes to be included in WP DXP tag given the chosen condition
	 *
	 * @param  StdClass $condition
	 * @param  string   $action
	 * @return array
	 */
	public function tagAttributes( $condition, $action ) : array {
		return [];
	}
}
