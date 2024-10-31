<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class PersonalizeWP_Category extends PersonalizeWP_Base_Model {

	protected static $table = 'pwp_categories';

	protected $attributes = [
		'id',
		'name',
		'created_at',
		'modified_at',
	];

	// Order matching attributes
	protected $data_format = [
		'%d',
		'%s',
		'%s',
		'%s',
	];

	/**
	 * Constructor
	 *
	 * @param array $attributes
	 */
	public function __construct( $attributes = [] ) {
		parent::__construct( $attributes );
	}

	/**
	 * Retrieve Edit URL for admin
	 *
	 * @return string
	 */
	public function getEditUrlAttribute() {
		return PERSONALIZEWP_ADMIN_CATEGORIES_EDIT_URL . $this->id;
	}

	/**
	 * Retrieve Delete URL for admin
	 *
	 * @return string
	 */
	public function getDeleteUrlAttribute() {
		return PERSONALIZEWP_ADMIN_CATEGORIES_DELETE_URL . $this->id;
	}

	/**
	 * Retrieve View Rules URL for admin
	 *
	 * @return string
	 */
	public function getRulesUrlAttribute() {
		return PERSONALIZEWP_ADMIN_CATEGORIES_RULES_URL . $this->id;
	}

	/**
	 * Retrieve Show URL for admin
	 *
	 * @return string
	 */
	public function getShowUrlAttribute() {
		return PERSONALIZEWP_ADMIN_CATEGORIES_SHOW_URL . $this->id;
	}

	/**
	 * Return whether category can be edited
	 *
	 * @return boolean
	 */
	public function getCanEditAttribute() {
		return true;
	}

	/**
	 * Return whether category can be deleted
	 *
	 * @return boolean
	 */
	public function getCanDeleteAttribute() {
		return empty( $this->getRulesCountAttribute() );
	}

	/**
	 * Return all rules related to category
	 *
	 * @return array
	 */
	public function getRulesAttribute() {
		return $this->getRules();
	}

	/**
	 * Wrapper to selectively clear additional cache during save
	 */
	public function save() {

		wp_cache_delete( 'category_rules_count_' . $this->id, 'personalizewp' );
		wp_cache_delete( 'category_rules_' . $this->id, 'personalizewp' );

		parent::save();
	}

	/**
	 * Return array of rules that use this category
	 *
	 * @return array
	 */
	public function getRules() {
		global $wpdb;

		$cache_key = 'category_rules_' . $this->id;
		$rows      = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $rows ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pwp_rules WHERE category_id = %d", $this->id ) );
			// phpcs:enable
			wp_cache_set( $cache_key, $rows, 'personalizewp' );
		}

		$return = [];
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$return[] = new PersonalizeWP_Rule( $row );
			}
		}

		return $return;
	}

	/**
	 * Return count of rules that use this category
	 *
	 * @return int
	 */
	public function getRulesCountAttribute() {
		return self::get_rules_count( $this->id );
	}

	/**
	 * Return count of rules that use this category
	 *
	 * @since 1.2.0
	 *
	 * @param int $id Category ID
	 *
	 * @return int
	 */
	public static function get_rules_count( $id ) {
		global $wpdb;

		$cache_key = 'category_rules_count_' . $id;
		$count     = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $count ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$wpdb->prefix}pwp_rules WHERE category_id = %d", $id ) );
			// phpcs:enable
			wp_cache_set( $cache_key, $count, 'personalizewp' );
		}

		return $count;
	}

	/**
	 * Populate model from an array of data
	 *
	 * @param  array $data
	 */
	public function populateFromArray( $data ) {
		$this->name = $data['name'];
	}
}
