<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class PersonalizeWP_Block extends PersonalizeWP_Base_Model {

	/**
	 * Active Block table
	 *
	 * @since 1.0.0
	 */
	protected static $table = 'pwp_active_blocks';

	/**
	 * Matches to database columns
	 *
	 * The block ID is randomly generated from the date on every change of a DXP rule.
	 */
	protected $attributes = [
		'block_ref', // Changed from id
		'rule_id',
		'name',
		'post_id',
	];

	// Order matching attributes
	protected $data_format = [
		'%s', // Changed from int
		'%d',
		'%s',
		'%d',
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
	 * Legacy col support
	 *
	 * @since  2.6.0
	 *
	 * @param  string $property
	 * @return mixed
	 */
	public function __get( $property ) {
		if ( 'id' === $property ) {
			$property = 'block_ref';
		}
		return parent::__get( $property );
	}

	/**
	 * Save Model
	 *
	 * @return null
	 */
	public function save() {
		global $wpdb;

		// We only ever insert a block, select or delete, never update.
		// Because the ID changes on every edit of the block.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			"{$wpdb->prefix}pwp_active_blocks",
			array(
				'block_ref' => $this->data['block_ref'],
				'rule_id'   => $this->data['rule_id'],
				'name'      => $this->data['name'],
				'post_id'   => $this->data['post_id'],
			),
			// Data sanitization format.
			$this->data_format
		);
		// phpcs:enable

		return null;
	}

	/**
	 * Delete is disabled
	 *
	 * @param  integer $id Block ID
	 * @return boolean
	 */
	public static function delete( $id ) {
		_doing_it_wrong( 'Block::delete', esc_html__( 'Need to use function `Block::delete_blocks()`', 'personalizewp' ), '1.0' );
		return null;
	}

	/**
	 * Delete all blocks for specified Post ID
	 *
	 * @param  integer $post_id Post ID
	 * @return boolean
	 */
	public static function delete_blocks( $post_id ) {
		global $wpdb;

		// Most of the cache is based on rule_ids, so need to loop and delete.
		$blocks = self::in_post( $post_id );
		foreach ( $blocks as $block ) {
			$rule_id = $block->rule_id;

			wp_cache_delete_multiple(
				array(
					'pwp_active_blocks_count_' . $rule_id,
					'pwp_active_blocks_usage_' . $rule_id,
					'pwp_active_blocks_rule_count_' . $rule_id,
					'pwp_active_blocks_rule_' . $rule_id,
				),
				'personalizewp'
			);
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}pwp_active_blocks WHERE post_id = %d;", $post_id ) );
		// phpcs:enable
		wp_cache_delete( 'pwp_active_blocks_post_' . $post_id, 'personalizewp' );

		return $result;
	}

	/**
	 * Get all blocks for specified Post ID
	 *
	 * @param  integer $post_id Post ID
	 * @return array
	 */
	public static function in_post( $post_id ) {
		global $wpdb;

		$cache_key = 'pwp_active_blocks_post_' . $post_id;
		$rows      = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $rows ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pwp_active_blocks WHERE post_id = %d;", $post_id ), ARRAY_A );
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
	 * Return array of post ids that use a particular rule
	 *
	 * @param  integer $rule_id
	 * @return array
	 */
	public static function getUsagePosts( $rule_id ) {
		global $wpdb;

		$cache_key = 'pwp_active_blocks_rule_' . $rule_id;
		$post_ids  = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $post_ids ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
			$post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}pwp_active_blocks WHERE rule_id = %d GROUP BY post_id", $rule_id ), ARRAY_N );
			// phpcs:enable
			wp_cache_set( $cache_key, $post_ids, 'personalizewp' );
		}

		if ( empty( $post_ids ) ) {
			return [];
		}

		$args = [
			'post__in'            => $post_ids,
			'posts_per_page'      => count( $post_ids ),
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		];

		$get_posts = new WP_Query();
		return $get_posts->query( $args );
	}

	/**
	 * Return count of posts that use a particular rule
	 *
	 * @param  integer $rule_id
	 * @return int
	 */
	public static function getUsagePostsCount( $rule_id ) {
		global $wpdb;

		$cache_key = 'pwp_active_blocks_rule_count_' . $rule_id;
		$result    = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $result ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
			$result = (int) $wpdb->get_var( $wpdb->prepare( "SELECT count(post_id) FROM {$wpdb->prefix}pwp_active_blocks WHERE rule_id = %d GROUP BY post_id", $rule_id ) );
			// phpcs:enable
			wp_cache_set( $cache_key, $result, 'personalizewp' );
		}

		return absint( $result );
	}

	/**
	 * Return array of blocks from posts that use a particular rule
	 *
	 * @param  integer $rule_id
	 * @return array
	 */
	public static function getUsageBlocks( $rule_id ) {
		global $wpdb;

		$cache_key = 'pwp_active_blocks_usage_' . $rule_id;
		$result    = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $result ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
			$result = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$wpdb->prefix}pwp_active_blocks.*, {$wpdb->posts}.post_title
				FROM {$wpdb->prefix}pwp_active_blocks LEFT JOIN {$wpdb->posts} ON {$wpdb->prefix}pwp_active_blocks.post_id = {$wpdb->posts}.ID
				WHERE {$wpdb->prefix}pwp_active_blocks.rule_id = %d",
					$rule_id
				)
			);
			// phpcs:enable
			wp_cache_set( $cache_key, $result, 'personalizewp' );
		}

		return $result;
	}

	/**
	 * Return count of blocks that use a particular rule
	 *
	 * @param  integer $rule_id
	 * @return int
	 */
	public static function getUsageBlocksCount( $rule_id ) {
		global $wpdb;

		$cache_key = 'pwp_active_blocks_count_' . $rule_id;
		$result    = wp_cache_get( $cache_key, 'personalizewp' );
		if ( false === $result ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Most performant way
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT count(block_ref) FROM {$wpdb->prefix}pwp_active_blocks WHERE rule_id = %d", $rule_id ) );
			// phpcs:enable
			wp_cache_set( $cache_key, $result, 'personalizewp' );
		}

		return absint( $result );
	}
}
