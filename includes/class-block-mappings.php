<?php
/**
 * Class Block Mappings
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 */

namespace PersonalizeWP;

use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Mapping block references to post reference.
 */
class Block_Mappings {

	use SingletonTrait;

	/**
	 * Name of cache for mappings
	 *
	 * @var string
	 */
	protected $cache_group = 'pwp_block_mappings';

	/**
	 * Block Mapping database table
	 *
	 * @var string
	 */
	protected $table = 'pwp_block_mappings';

	/**
	 * Matches to database columns
	 *
	 * @var array
	 */
	protected $attributes = [
		'block_ref',
		'post_ref',
		'map_type',
	];

	/**
	 * Order matching attributes
	 *
	 * @var array
	 */
	protected $data_format = [
		'%s',
		'%s',
		'%s',
	];

	/**
	 * Save block mapping
	 *
	 * @param string $block_ref Block reference
	 * @param string $post_ref  Post reference
	 * @param string $map_type  Post Mapping type, defaults to 'block-editor'
	 *
	 * @return bool True on success, false otherwise
	 */
	public function save_mapping( $block_ref, $post_ref, $map_type = 'block-editor' ) {
		global $wpdb;

		// We only ever insert a block, select or delete, never update.
		// Because the ID changes on every edit of the block.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$success = $wpdb->insert(
			$wpdb->prefix . $this->table,
			array(
				'block_ref' => $block_ref,
				'post_ref'  => $post_ref,
				'map_type'  => $map_type,
			),
			// Data sanitization format.
			$this->data_format
		);
		// phpcs:enable

		return (bool) $success;
	}

	/**
	 * Save multiple block mappings at once
	 *
	 * @param array $block_mappings Multiple block mappings
	 *
	 * @return bool True on success, false otherwise
	 */
	public function save_mappings( $block_mappings ) {

		if ( ! is_array( $block_mappings ) ) {
			return false;
		}

		foreach ( $block_mappings as $mapping ) {
			list( 'block_ref' => $block_ref, 'post_ref' => $post_ref, 'map_type' => $map_type ) = $mapping;
			$result = $this->save_mapping( $block_ref, $post_ref, $map_type );
			if ( ! $result ) {
				// Error, immediately halt
				return false;
			}
		}

		return true;
	}

	/**
	 * Get singular block mapping
	 *
	 * @param string $block_ref Block reference
	 *
	 * @return array[]  {
	 *    Singular block mapping entry
	 *
	 *    @type string  $block_ref  Reference of block.
	 *    @type string  $post_ref   Reference of original Post.
	 *    @type string  $map_type   Mapping type.
	 * }
	 */
	public function get_block_mapping( $block_ref ) {
		global $wpdb;

		$block = wp_cache_get( $block_ref, $this->cache_group );
		if ( false === $block ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
			$block = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pwp_block_mappings WHERE block_ref = %s LIMIT 1", $block_ref ), ARRAY_A );
			// phpcs:enable
			wp_cache_set( $block_ref, $block, $this->cache_group );
		}

		return $block;
	}

	/**
	 * Get multiple block mappings
	 *
	 * @param array $block_refs Block references
	 *
	 * @return array[]  {
	 *     Associate array of block mappings
	 *
	 *     @type array ...$0 {
	 *         An associative array of a single parsed block mapping. See get_block_mapping.
	 *
	 *         @type string  $block_ref  Reference of block.
	 *         @type string  $post_ref   Reference of original Post.
	 *         @type string  $map_type   Mapping type.
	 *     }
	 * }
	 */
	public function get_block_mappings( $block_refs ) {
		global $wpdb;

		$non_cached_refs = $this->get_non_cached_refs( $block_refs, $this->cache_group );
		if ( ! empty( $non_cached_refs ) ) {
			$fresh_blocks = $wpdb->get_results(
				$wpdb->prepare(
					sprintf(
						"SELECT * FROM {$wpdb->prefix}pwp_block_mappings WHERE block_ref IN (%s)",
						implode( ',', array_fill( 0, count( $non_cached_refs ), '%s' ) )
					),
					$non_cached_refs
				),
				ARRAY_A
			);
			if ( $fresh_blocks ) {
				// Despite the name, update_cache() expects an array rather than a single block.
				$this->update_cache( $fresh_blocks, $this->cache_group );
			}
		}

		$mappings = array_map( [ $this, 'get_block_mapping' ], $block_refs );
		$blocks   = [];
		foreach ( $mappings as $map ) {
			// Converted to associate array
			$blocks[ $map['block_ref'] ] = $map;
		}

		return $blocks;
	}

	/**
	 * Retrieves references that are not already present in the cache.
	 *
	 * @param string[] $block_refs  Array of references.
	 * @param string   $cache_group The cache group to check against.
	 * @return string[] Array of references not present in the cache.
	 */
	private function get_non_cached_refs( $block_refs, $cache_group ) {
		$block_refs = array_unique( array_filter( $block_refs ) );

		if ( empty( $block_refs ) ) {
			return array();
		}

		$non_cached_refs = array();
		$cache_values    = wp_cache_get_multiple( $block_refs, $cache_group );

		foreach ( $cache_values as $ref => $value ) {
			if ( false === $value ) {
				$non_cached_refs[] = $ref;
			}
		}

		return $non_cached_refs;
	}

	/**
	 * Updates blocks in cache.
	 *
	 * @param array[] $blocks      Array of blocks
	 * @param string  $cache_group The cache group to store against.
	 */
	private function update_cache( $blocks, $cache_group ) {
		if ( ! $blocks ) {
			return;
		}

		$data = array();
		foreach ( $blocks as $block ) {
			$data[ $block['block_ref'] ] = $block;
		}
		wp_cache_add_multiple( $data, $cache_group );
	}

	/**
	 * Get all block mappings by Post ID
	 *
	 * @param string $post_ref Post ID/Reference
	 *
	 * @return array[]  {
	 *     Array of block mappings
	 *
	 *     @type array {
	 *         An array of a single parsed block object. See get_block.
	 *
	 *         @type string  $block_ref  Reference of block.
	 *         @type string  $post_ref   Reference of original Post.
	 *         @type string  $map_type   Mapping type.
	 *     }
	 * }
	 */
	public function get_post_mappings( $post_ref ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
		$blocks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pwp_block_mappings WHERE post_ref = %s", $post_ref ), ARRAY_A );
		// phpcs:enable

		return $blocks;
	}

	/**
	 * Deletes block from all related caches
	 *
	 * @param string $block_ref Block reference
	 *
	 * @return void
	 */
	protected function delete_block_cache( $block_ref ) {

		// Remove individual block
		wp_cache_delete( $block_ref, $this->cache_group );
	}

	/**
	 * Delete block mapping
	 *
	 * @param string $block_ref Block reference
	 *
	 * @return bool
	 */
	public function delete_mapping( $block_ref ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}pwp_block_mappings WHERE block_ref = %s LIMIT 1", $block_ref ) );
		// phpcs:enable

		if ( $result ) {
			$this->delete_block_cache( $block_ref );
		}

		return (bool) $result;
	}

	/**
	 * Delete all block mappings for specified Post ID
	 *
	 * @param  string $post_ref Post reference
	 * @return boolean
	 */
	public function delete_post_mappings( $post_ref ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}pwp_block_mappings WHERE post_ref = %s;", $post_ref ) );
		// phpcs:enable

		if ( $result ) {
			wp_cache_flush_group( $this->cache_group );
		}

		return (bool) $result;
	}
}
