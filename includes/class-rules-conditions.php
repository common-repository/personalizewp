<?php
/**
 * PersonalizeWP
 *
 * @link    https://personalizewp.com
 * @since   1.0.0
 *
 * @package PersonalizeWP
 */

namespace PersonalizeWP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Container and router of all Rule Conditions
 */
class Rules_Conditions {

	/**
	 * List of known conditions to use for Rules
	 *
	 * @var array
	 */
	protected static $conditions = [
		'core_visitor_country',
		'core_is_logged_in_user',
		'core_time_elapsed',
		'core_new_visitor',
		'core_users_last_visit',
		'core_users_visiting_time',
		'core_users_specific_visiting_time',
		'core_users_device_type',
		'core_users_role',
		'core_visiting_date',
		'core_visiting_day',
		'core_query_string',
		'core_referrer',
		'core_cookie',
		'utm_campaign',
		'utm_content',
		'utm_medium',
		'utm_source',
		'utm_term',
		'woocommerce_cart_contents',
		'woocommerce_completed_purchase',
		'woocommerce_total_spend',
		'woocommerce_total_products_purchased',
	];

	/**
	 * Get Rule class instance for specified condition identifier
	 *
	 * @param  string $identifier
	 *
	 * @return object|null
	 */
	public static function get_class( $identifier ) {

		$condition = null;
		$namespace = '\PersonalizeWP\Rule_Conditions\\';
		$class     = '';

		switch ( $identifier ) {
			case 'utm_campaign':
			case 'utm_content':
			case 'utm_medium':
			case 'utm_source':
			case 'utm_term':
				// Simplify the identifier for class mapping
				$class = "{$namespace}UTM\\" . ucwords( str_replace( 'utm_', '', $identifier ) );
				break;

			case 'core_visitor_country':
			case 'core_is_logged_in_user':
			case 'core_time_elapsed':
			case 'core_new_visitor':
			case 'core_users_last_visit':
			case 'core_users_visiting_time':
			case 'core_users_specific_visiting_time':
			case 'core_users_device_type':
			case 'core_users_role':
			case 'core_visiting_date':
			case 'core_visiting_day':
			case 'core_query_string':
			case 'core_referrer':
			case 'core_cookie':
			case 'woocommerce_cart_contents':
			case 'woocommerce_completed_purchase':
			case 'woocommerce_total_spend':
			case 'woocommerce_total_products_purchased':
				// Simplify the identifier for class mapping
				$class = $namespace . ucwords( str_replace( 'core_', '', $identifier ), '_' );
				break;
		}

		if ( ! empty( $class ) && class_exists( $class ) && method_exists( $class, 'instance' ) ) {
			$condition = $class::instance();
		}

		/**
		 * Filters all condition identifiers for expanded class instance mapping
		 *
		 * @since 1.0.0
		 *
		 * @param object $condition Instance of class
		 * @param string $identifier Name of Rule Condition
		 */
		return apply_filters( 'personalizewp_rule_condition_indentifier', $condition, $identifier );
	}

	/**
	 * Returns self::get_known_conditions() passed through a filter for extending
	 *
	 * @return array Valid Conditions
	 */
	public static function get_known_conditions() {

		/**
		 * Filters the array of known conditions
		 *
		 * @since 1.0.0
		 *
		 * @param array $conditions
		 */
		return apply_filters(
			'personalizewp_known_rule_conditions',
			self::$conditions
		);
	}

	/**
	 * List of all Rules grouped by their category, ordered by description
	 *
	 * @return array
	 */
	public static function grouped_list() {
		$return     = [];
		$categories = [];

		foreach ( self::get_known_conditions() as $identifier ) {

			$class = self::get_class( $identifier );
			if ( is_null( $class ) ) {
				continue;
			}
			if ( method_exists( $class, 'dependencies' ) && count( $class->dependencies() ) > 0 ) {
				foreach ( $class->dependencies() as $dependency ) {
					if ( ! $dependency->verify() ) {
						continue 2;
					}
				}
			}

			$category     = self::get_class_category( $class );
			$categories[] = $category;
			$description  = self::get_class_description( $class );

			$return[ $category ][ $identifier ] = $description;
		}

		// Sort the translated categories alphabetically
		ksort( $return );

		// Sort Conditions by their translated descriptions alphabetically, within categories
		foreach ( $categories as $category ) {
			asort( $return[ $category ] );
		}

		return $return;
	}

	/**
	 * List of all Rules, ordered by description
	 *
	 * @return array
	 */
	public static function list() {
		$return = [];

		foreach ( self::get_known_conditions() as $identifier ) {

			$class = self::get_class( $identifier );
			if ( is_null( $class ) ) {
				continue;
			}
			if ( method_exists( $class, 'dependencies' ) && count( $class->dependencies() ) > 0 ) {
				foreach ( $class->dependencies() as $dependency ) {
					if ( ! $dependency->verify() ) {
						continue;
					}
				}
			}

			$description = self::get_class_description( $class );

			$return[ $identifier ] = $description;
		}

		// Sort Conditions by their translated descriptions
		asort( $return );

		return $return;
	}

	/**
	 * Get Rule class description from the condition class, with fallback if not available.
	 *
	 * @param object $class Class instance
	 *
	 * @return string
	 */
	private static function get_class_description( $class ) {

		return ! empty( $class->description ) ? $class->description : _x( 'Unknown', 'Class description', 'personalizewp' );
	}

	/**
	 * Get Rule class category from the condition class
	 *
	 * @param object $class Class instance
	 *
	 * @return string
	 */
	private static function get_class_category( $class ) {

		return ! empty( $class->category ) ? $class->category : _x( 'Misc', 'Class category', 'personalizewp' );
	}

	/**
	 * Get list of comparators for specified condition identifier
	 *
	 * @param  string $identifier
	 * @return array
	 */
	private static function getComparatorList( $identifier ) {
		$ruleClass = self::get_class( $identifier );

		if ( $ruleClass ) {
			return $ruleClass->getComparators();
		}

		return [];
	}

	/**
	 * Get list of comparison values for specified condition identifier
	 *
	 * @param  string $identifier
	 * @return array
	 */
	private static function getComparisonValues( $identifier ) {
		$ruleClass = self::get_class( $identifier );

		if ( $ruleClass ) {
			return $ruleClass->getComparisonValues();
		}

		return [];
	}

	/**
	 * Get list of comparators for default condition
	 *
	 * @return array
	 */
	public static function getDefaultComparatorList() {
		_deprecated_function( __FUNCTION__, '2.5.0' );

		$list = self::list();

		$firstIdentifier = array_key_first( $list );

		return self::getComparatorList( $firstIdentifier );
	}

	/**
	 * Get list of comparison values for default condition
	 *
	 * @return array
	 */
	public static function getDefaultComparisonValues() {
		_deprecated_function( __FUNCTION__, '2.5.0' );

		$list = self::list();

		$firstIdentifier = array_key_first( $list );

		return self::getComparisonValues( $firstIdentifier );
	}

	/**
	 * Return JSON string with all conditions and their data, used for populating form select fields in WP-Admin
	 *
	 * @return string
	 */
	public static function to_object() {
		$conditions = self::list();

		$return = [];
		foreach ( $conditions as $identifier => $condition ) {
			$conditionClass = self::get_class( $identifier );

			$return[ $identifier ] = $conditionClass->toObject();
		}

		return $return;
	}
}
