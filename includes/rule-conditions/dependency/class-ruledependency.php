<?php
/**
 * Base Rule Dependancy for extending.
 *
 * @link       https://personalizewp.com
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions/Dependancy
 */

namespace PersonalizeWP\Rule_Conditions\Dependency;

use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Rule Dependency abstract class, all rule dependancies to extend.
 */
abstract class RuleDependency {

	use SingletonTrait;

	protected string $description;

	protected string $success_message;

	protected string $failure_message;

	/**
	 * Execute test to check if dependency is met
	 *
	 * @return bool
	 */
	abstract public function verify() : bool;

	/**
	 * Return the failure message if the dependancy isn't met
	 *
	 * @return string
	 */
	public function get_failure_message() {
		return $this->failure_message;
	}
}
