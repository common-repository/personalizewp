<?php
/**
 * User roles type condition Rules
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
 * Checks for the existance of conditions against a users Role
 */
class Users_Role extends RuleCondition {

	public string $identifier = 'core_users_role';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'User Role', 'personalizewp' );

		$this->comparators = [
			'equals'         => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
			'does_not_equal' => _x( 'Does Not Equal', 'Comparator', 'personalizewp' ),
		];

		$this->comparison_values = get_option( 'pwp_editor_role_values', [] );
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
			case 'does_not_equal':
				return $this->comparatorDoesNotEqual( $value );
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value ) {
		$currentUsersRoles = $this->get_current_users_roles();

		if ( in_array( $value, $currentUsersRoles ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * "Does not equal" test
	 *
	 * @return bool
	 */
	public function comparatorDoesNotEqual( $value ) {

		$currentUsersRoles = $this->get_current_users_roles();

		if ( ! in_array( $value, $currentUsersRoles ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Fetch, prep and store editor roles in wp_options table
	 */
	public static function setComparisonValues() {
		$roles      = [];
		$role_names = get_editable_roles();

		foreach ( $role_names as $role_info ) :
			$roleName         = $role_info['name'];
			$currentRoleArray = array( strtolower( $roleName ) => $roleName );
			$roles            = $roles + $currentRoleArray;
		endforeach;

		update_option( 'pwp_editor_role_values', $roles, false );
	}

	/**
	 * Get current users roles
	 *
	 * @return array
	 */
	public function get_current_users_roles() {
		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$roles = (array) $user->roles;
			return $roles; // This will returns an array
		} else {
			return array();
		}
	}
}
