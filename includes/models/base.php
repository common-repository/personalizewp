<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class PersonalizeWP_Base_Model {

	protected static $table = '';

	protected $data = [];

	protected $attributes = [];

	// Order matching attributes
	protected $data_format = [];

	/**
	 * Constructor
	 *
	 * @param array $attributes [description]
	 */
	public function __construct( $attributes = [] ) {
		foreach ( $this->attributes as $attribute ) {
			$this->$attribute = null;
		}

		if ( $attributes ) {
			$this->hydrateFromArray( $attributes );
		}
	}

	/**
	 * Save Model
	 *
	 * @return null
	 */
	public function save() {
		global $wpdb;

		$table             = self::getTableName();
		$this->modified_at = gmdate( 'Y-m-d H:i:s' );

		$data = [];
		// Filter obj data to what is needed for the database, removing cache data.
		foreach ( $this->attributes as $attribute ) {
			$data[ $attribute ] = $this->data[ $attribute ] ?? '';
		}

		if ( $this->id ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery  -- Reason: Most performant way.
			$wpdb->update( $wpdb->prefix . $table, $data, [ 'id' => $this->id ], $this->data_format, '%d' );
			// phpcs:enable
		} else {
			$data['created_at'] = $this->created_at = gmdate( 'Y-m-d H:i:s' );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery  -- Reason: Most performant way.
			$wpdb->insert( $wpdb->prefix . $table, $data, $this->data_format );
			// phpcs:enable
			$this->id = $wpdb->insert_id;
			// Ensure stored for cache.
			$data['id'] = $this->id ;
		}
		// Replace/update cache of item.
		wp_cache_set( $this->id, $data, $table );
		// Clear additional cache
		wp_cache_delete( $table . '-all', 'personalizewp' );
	}

	/**
	 * Populate model attributes from array of data
	 *
	 * @param  array $attributes
	 */
	protected function hydrateFromArray( $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Find model via ID
	 *
	 * @param  integer $id
	 * @return PersonalizeWP_Base_Model|false
	 */
	public static function find( $id ) {
		global $wpdb;

		$table = self::getTableName();

		$attributes = wp_cache_get( $id, $table );
		if ( false === $attributes ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared  -- Reason: tablename is dynamic
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
			$attributes = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$table} WHERE id = %d", $id ), ARRAY_A );
			// phpcs:enable
			wp_cache_set( $id, $attributes, $table );

			if ( ! $attributes ) {
				return false;
			}
		}
		return new static( $attributes );
	}

	/**
	 * Find model via ID
	 *
	 * @param  string $ids
	 * @return array|false
	 */
	public static function findAll( $ids ) {
		global $wpdb;

		$table = self::getTableName();

		$ids = array_unique( array_filter( explode( ',', $ids ) ) );

		if ( 1 === count( $ids ) ) {
			return [ self::find( $ids[0] ) ];
		}

		$attributes = wp_cache_get_multiple( $ids, $table );
		$missingIds = array_keys(
			array_filter(
				$attributes,
				function ( $i ) {
					return $i === false;
				}
			)
		);

		if ( count( $missingIds ) > 0 ) {
			$placeholders = implode( ', ', array_fill( 0, count( $missingIds ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: tablename is dynamic
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholders are set above
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching  -- Reason: query used to populate caches
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
			$missingAttributes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$table} WHERE id IN ( {$placeholders} )", $missingIds ), ARRAY_A );
			// phpcs: enable

			if ( $missingAttributes ) {
				foreach ( $missingAttributes as $attribute ) {
					$attributes[ $attribute['id'] ] = $attribute;
					wp_cache_set( $attribute['id'], $attribute, $table );
				}
			}
		}

		$attributes = array_map(
			function ( $attribute ) {
				return new static( $attribute );
			},
			array_filter( $attributes )
		);

		return $attributes;
	}

	/**
	 * Check uniqueness via name
	 * Used when cloning, and during Category/Rule validation.
	 *
	 * @param  string  $name
	 * @param  integer $id
	 * @return string|null
	 */
	public static function check_name( $name, $id = 0 ) {
		global $wpdb;

		$table = self::getTableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared  -- Reason: tablename is dynamic
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching  -- Reason: no relevant caches
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
		return $wpdb->get_var( $wpdb->prepare( "SELECT `name` FROM {$wpdb->prefix}{$table} WHERE `name` = %s AND ID != %d LIMIT 1", $name, $id ) );
		// phpcs:enable
	}

	/**
	 * Get all models
	 *
	 * @return array
	 */
	public static function all() {
		global $wpdb;

		$table = self::getTableName();

		$cache_key = $table . '-all';
		$rows      = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $rows ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared  -- Reason: tablename is dynamic
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}{$table} ORDER BY name ASC;", ARRAY_A );
			// phpcs:enable
			wp_cache_set( $cache_key, $rows, 'personalizewp' );
		}

		$return = [];
		foreach ( $rows as $row ) {
			$return[] = new static( $row );
		}

		return $return;
	}

	/**
	 * Delete model for specified ID
	 *
	 * @param  integer $id
	 * @return boolean
	 */
	public static function delete( $id ) {
		global $wpdb;

		$table = self::getTableName();

		// Remove cached entry.
		wp_cache_delete( $id, $table );
		// Clear additional cache
		wp_cache_delete( $table . '-all', 'personalizewp' );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared  -- tablename is dynamic
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way.
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}{$table} WHERE id = %d", $id ) );
		// phpcs:enable
	}

	/**
	 * Get models as list with specified properties as key & value
	 *
	 * @return array
	 */
	public static function list( $key = 'id', $value = 'name' ) {
		$rows = static::all();

		$list = [];
		foreach ( $rows as $row ) {
			$list[ $row->$key ] = $row->$value;
		}

		return $list;
	}

	/**
	 * Magic Getter
	 *
	 * @param  string $property
	 * @return mixed
	 */
	public function __get( $property ) {
		// Check for db based data via defined attributes, or custom data in non-persistent cache.
		if ( in_array( $property, $this->attributes, true ) || array_key_exists( $property, $this->data ) ) {
			return stripslashes_deep( $this->data[ $property ] );
		}

		// Allow for custom attribute getters
		$attrMethodName = 'get' . str_replace( '_', '', ucwords( $property, '_' ) ) . 'Attribute';

		if ( method_exists( $this, $attrMethodName ) ) {
			// Store results of method as non-persistent cache on object.
			$this->data[ $property ] = stripslashes_deep( $this->$attrMethodName() );
			return $this->data[ $property ];
		}

		return null;
	}

	/**
	 * Magic setter
	 *
	 * @param string $property
	 * @param mixed  $value
	 */
	public function __set( $property, $value ) {
		if ( in_array( $property, $this->attributes ) ) {
			$this->data[ $property ] = $value;
		}
	}

	/**
	 * Return the table name for the class
	 *
	 * @return string
	 */
	public static function getTableName() {
		return static::$table;
	}
}
