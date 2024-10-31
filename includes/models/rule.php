<?php

use \PersonalizeWP\Rules_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class PersonalizeWP_Rule extends PersonalizeWP_Base_Model {

	/**
	 * Database table name
	 *
	 * @var string
	 */
	protected static $table = 'pwp_rules';

	/**
	 * Default operator, used when multiple conditions are evaluated.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public static $default_operator = 'ALL';

	/**
	 * Matches to database columns
	 *
	 * @var array
	 */
	protected $attributes = [
		'id',
		'name',
		'category_id',
		'type',
		'conditions_json',
		'operator', // String indicator of how multiple conditions are evaluated.
		'created_by',
		'created_at',
		'modified_at',
	];

	/**
	 * Format of database columns
	 *
	 * @var array
	 */
	protected $data_format = [
		'%d',
		'%s',
		'%d',
		'%s',
		'%s',
		'%s', // Operator
		'%s',
		'%s',
		'%s',
	];

	/**
	 * Constructor
	 *
	 * @param array $data Data to populate against attributes
	 */
	public function __construct( $data = [] ) {

		// Ensure database defaults are enforced.
		$data = wp_parse_args(
			$data,
			[
				'operator' => self::$default_operator,
			]
		);

		parent::__construct( $data );
	}

	/**
	 * Retrieve friendly name of category the rule is associated with
	 *
	 * @return string
	 */
	public function getCategoryNameAttribute() {
		$category = PersonalizeWP_Category::find( $this->category_id );

		return $category ? $category->name : esc_html_x( 'Unknown', 'Category name', 'personalizewp' );
	}

	/**
	 * Conditions in array, decoded from JSON
	 *
	 * @return array
	 */
	public function getConditionsAttribute() {
		$conditions = $this->conditions_json ? json_decode( $this->conditions_json ) : [];

		$return = [];
		foreach ( $conditions as $condition ) {
			$condition->raw_value = $condition->comparator === 'any' ? wp_json_encode( $condition->value ) : $condition->value;

			// Ensure existence of required data
			if ( ! isset( $condition->meta ) ) {
				$condition->meta = [];
			}

			$return[] = $condition;
		}

		return $return;
	}

	/**
	 * Retrieve friendly name of rule type
	 *
	 * @return string
	 */
	public function getTypeFriendlyAttribute() {
		_deprecated_function( __FUNCTION__, '2.6.0' );
		return PersonalizeWP_Rule_Types::getName( $this->type );
	}

	/**
	 * Retrieve name of use (or Plugin) that created the rule
	 *
	 * @return string
	 */
	public function getCreatedByFriendlyAttribute() {
		_deprecated_function( __FUNCTION__, '2.6.0' );
		if ( ! $this->created_by ) {
			return esc_html_x( 'Plugin', 'Created by user name', 'personalizewp' );
		}

		$user = get_userdata( $this->created_by );

		return empty( $user->display_name ) ? esc_html_x( 'Unknown', 'Created by user name', 'personalizewp' ) : $user->display_name;
	}

	/**
	 * Retrieve Edit URL for admin
	 *
	 * @return string
	 */
	public function getEditUrlAttribute() {
		return PERSONALIZEWP_ADMIN_RULES_EDIT_URL . $this->id;
	}

	/**
	 * Retrieve Delete URL for admin
	 *
	 * @return string
	 */
	public function getDeleteUrlAttribute() {
		return PERSONALIZEWP_ADMIN_RULES_DELETE_URL . $this->id;
	}

	/**
	 * Retrieve Duplicate URL for admin
	 *
	 * @return string
	 */
	public function getDuplicateUrlAttribute() {
		return PERSONALIZEWP_ADMIN_RULES_DUPLICATE_URL . $this->id;
	}

	/**
	 * Retrieve Show URL for admin
	 *
	 * @return string
	 */
	public function getShowUrlAttribute() {
		_deprecated_function( __FUNCTION__, '2.6.0' );
		return PERSONALIZEWP_ADMIN_RULES_SHOW_URL . $this->id;
	}

	/**
	 * Return whether rule can be edited
	 *
	 * @return boolean
	 */
	public function getCanEditAttribute() {
		_deprecated_function( __FUNCTION__, '2.6.0' );
		return in_array( $this->type, [ PersonalizeWP_Rule_Types::$STANDARD, PersonalizeWP_Rule_Types::$CUSTOM ], true );
	}

	/**
	 * Return whether rule can be deleted
	 *
	 * @return boolean
	 */
	public function getCanDeleteAttribute() {
		// Only allow if no blocks use this rule
		return 0 === $this->usage_posts_count;
	}

	/**
	 * Return whether rule can be duplicated
	 *
	 * @return boolean
	 */
	public function getCanDuplicateAttribute() {
		return $this->is_usable;
	}

	/**
	 * Retrieve posts (with details) where this rule is used
	 *
	 * @return string
	 */
	public function getUsagePostsAttribute() {
		return PersonalizeWP_Block::getUsagePosts( $this->id );
	}

	/**
	 * Retrieve posts (with details) where this rule is used
	 *
	 * @return int
	 */
	public function getUsagePostsCountAttribute() {
		return PersonalizeWP_Block::getUsagePostsCount( $this->id );
	}

	/**
	 * Retrieve blocks (with details) where this rule is used
	 *
	 * @return string
	 */
	public function getUsageBlocksAttribute() {
		return PersonalizeWP_Block::getUsageBlocks( $this->id );
	}

	/**
	 * Retrieve number of blocks where this rule is used
	 *
	 * @return string
	 */
	public function getUsageBlocksCountAttribute() {
		return PersonalizeWP_Block::getUsageBlocksCount( $this->id );
	}

	/**
	 * Is the rule usable with the conditions' current dependencies?
	 *
	 * @return boolean
	 */
	public function getIsUsableAttribute() {
		// $dependencies = $this->getConditionDependencies();

		foreach ( $this->conditions as $condition ) {
			$classInstance = Rules_Conditions::get_class( $condition->measure );
			if ( $classInstance ) {
				$isUsable = $classInstance->isUsable();

				if ( ! $isUsable ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Retrieve all dependencies from the conditions associated with the rule
	 *
	 * @return array
	 */
	protected function getConditionDependencies() {
		$return = [];
		foreach ( $this->conditions as $condition ) {
			$classInstance = Rules_Conditions::get_class( $condition->measure );
			if ( $classInstance ) {
				$dependencies = $classInstance->dependencies();

				$return = array_merge( $return, $dependencies );
			}
		}

		return $return;
	}

	public function getConditionDependencyIssues() {
		$issues = [];

		$dependencies = $this->getConditionDependencies();

		foreach ( $dependencies as $dependency ) {
			if ( ! $dependency->verify() ) {
				$issues[] = $dependency->get_failure_message();
			}
		}

		return $issues;
	}

	/**
	 * Clone this rule and save
	 *
	 * @return PersonalizeWP_Rule
	 */
	public function clone() {
		$this->type       = PersonalizeWP_Rule_Types::$CUSTOM;
		$this->created_by = get_current_user_id();
		$this->id         = null;
		$this->name       = $this->name . _x( ' copy', 'post fix added to name on duplication', 'personalizewp' );

		// Names should be unique.
		$name_check = $this->check_name( $this->name );
		if ( $name_check ) {
			$suffix = 2;
			do {
				$alt_name   = substr( $this->name, 0, 200 ) . "-$suffix";
				$name_check = $this->check_name( $alt_name );
				++$suffix;
			} while ( $name_check );
			$this->name = $alt_name;
		}

		$this->save();

		return $this;
	}

	/**
	 * Conditions match criteria - used when deciding whether to render a block
	 *
	 * @param  string $action Action to be taken when matching
	 * @return boolean
	 */
	public function conditionsMatched( $action ) {

		foreach ( $this->conditions as $condition ) {
			$class_instance = Rules_Conditions::get_class( $condition->measure );
			if ( $class_instance ) {
				$criteria_matched = $class_instance->matchesCriteria( $condition->comparator, $condition->value, $action, $condition->meta );

				switch ( $this->operator ) {
					default:
					case 'ALL':
						// Require all conditions to match, so a single failure causes no match.
						if ( ! $criteria_matched ) {
							return false;
						}
						break;

					case 'ANY':
						// Require any condition to match, so a single success triggers true.
						if ( $criteria_matched ) {
							return true;
						}
						break;
				}
			}
		}

		switch ( $this->operator ) {
			default:
			case 'ALL':
				// Required all conditions to match, no failures must indicate a success.
				return true;

			case 'ANY':
				// Required any condition to match, no matches must indicate a failure.
				return false;
		}
	}

	/**
	 * Populate model from an array of data
	 *
	 * @param  array $data
	 */
	public function populateFromArray( $data ) {
		$this->name        = $data['name'];
		$this->category_id = (int) $data['category_id'];

		$valid_operators   = [ 'ANY', 'ALL' ];
		$is_valid_operator = ( ! empty( $data['operator'] ) && in_array( $data['operator'], $valid_operators, true ) );
		$this->operator    = $is_valid_operator ? $data['operator'] : self::$default_operator;

		$conditionCount = ! empty( $data['conditions']['measure'] ) ? count( $data['conditions']['measure'] ) : 0;

		$conditions = [];
		for ( $i = 0; $i < $conditionCount; $i++ ) {

			$metaArray  = [];
			$measure    = $data['conditions']['measure'][ $i ];
			$metaValue  = $data['conditions']['meta_value'][ $i ];
			$comparator = $data['conditions']['comparator'][ $i ];
			$value      = $data['conditions']['value'][ $i ];
			$rawValue   = $data['conditions']['raw_value'][ $i ];

			if ( ! empty( $metaValue ) ) :
				$conditionClass = Rules_Conditions::get_class( $measure );
				if ( $conditionClass ) :
					$measureKey = $conditionClass->measure_key;

					$metaArray[ $measureKey ] = $metaValue;
				endif;
			endif;

			$conditions[] = (object) [
				'measure'    => $measure,
				'meta'       => $metaArray,
				'comparator' => $comparator,
				'value'      => $comparator === 'any' ? json_decode( stripslashes( $rawValue ) ) : $rawValue,
			];
		}

		$this->conditions_json = wp_json_encode( $conditions );
	}

	/**
	 * Array of tag attributes to be included in WP DXP tag
	 *
	 * @param string $action the resulting action of the rule
	 * @return array
	 */
	public function tagAttributes( $action ) {

		$attributes = [];

		foreach ( $this->conditions as $condition ) {
			$classInstance = Rules_Conditions::get_class( $condition->measure );
			if ( $classInstance ) {
				$attributes = $classInstance->tagAttributes( $condition, $action ) + $attributes;
			}
		}

		return $attributes;
	}
}
