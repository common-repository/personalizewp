<?php
/**
 * Class Admin Blocks
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

use PersonalizeWP\Block_Mappings;
use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Post-processes blocks used in content to format for FE usage.
 */
class Admin_Blocks {

	use SingletonTrait;

	/**
	 * Cache of PWP blocks in post content during save
	 *
	 * @since 2.6.0
	 * @var array PersonalizeWP Blocks within current post content.
	 */
	protected $pwp_blocks = [];

	/**
	 * Stores the ->post_content state like md5( $post_content ) so we know
	 * whether we have to re-parse blocks because something has changed.
	 *
	 * @since 2.6.0
	 * @var bool|string
	 */
	private $content_hash = false;

	/**
	 * Whether the ->post_content has changed during saving, so we know
	 * whether we have to re-parse our blocks because something has changed
	 *
	 * @since 2.6.0
	 * @var bool
	 */
	private $content_changed = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		// Check for PWP blocks before saving to the database
		add_action( 'wp_insert_post_data', [ $this, 'pre_blocks_update' ], 100, 4 );

		// Process blocks after WP has stored in DB.
		add_action( 'save_post', [ $this, 'update_block_mappings' ], 100, 3 );

		add_action( 'delete_post', [ $this, 'delete_block_mappings' ], 100, 1 );
		add_action( 'delete_post', [ $this, 'delete_active_rule_blocks' ], 100, 1 );
	}

	/**
	 * Pre-processes a Post to check for PWP blocks, allowing the post_content to change before db storage.
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 * @param bool  $update              Whether this is an existing post being updated.
	 *
	 * @return $data Data for WordPress to store
	 */
	public function pre_blocks_update( $data, $postarr, $unsanitized_postarr, $update ) {

		// Verify if this is an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		// Skip revisions.
		if ( 'revision' === $data['post_type'] ) {
			return $data;
		}

		$post_content = wp_unslash( $data['post_content'] );
		if ( empty( $post_content ) ) {
			// Nothing to do.
			return $data;
		}

		// PWP blocks within content, assume none at start.
		$this->pwp_blocks      = [];
		$this->content_hash    = md5( $post_content );
		$this->content_changed = false;

		// Check content for PWP attributed blocks.
		if ( ! str_contains( $post_content, '"personalizewp":' ) &&
			/**
			 * Filters the logic checking Post Content to find if PWP has been used on blocks or not.
			 *
			 * Similar to the light check used on has_shortcode()
			 *
			 * @since 2.6.0
			 *
			 * @param bool   $contains_pwp_blocks  Whether the post content contains PWP content or not to post-process
			 * @param string $post_content         Current post content, sanitized and processed (by WP) but unslashed.
			 * @return bool Return true to continue processing post_content
			 */
			! apply_filters( 'personalizewp_post_content_contains_pwp', false, $post_content ) ) {

			// Nothing to do.
			return $data;
		}

		// Process the blocks at this point.
		$this->pwp_blocks = $this->find_all_blocks( parse_blocks( $post_content ) );

		foreach ( $this->pwp_blocks as $block ) {
			// Check for possible duplication of unique IDs.
			if ( ! empty( $block['attrs']['personalizewp']['blockID'] ) ) {

				$block_ref = $block['attrs']['personalizewp']['blockID'];
				// Check that no more than one occurrence exists for this ID, it *must* be unique.
				if ( 1 < substr_count( $post_content, '"blockID":"' . $block_ref . '"' ) ) {
					// Regenerate ID to ensure uniqueness. This change will be sent back to the block editor to update itself.
					$search  = sprintf( '"blockID":"%1$s"', $block_ref );
					$replace = sprintf( '"blockID":"%1$s"', wp_generate_uuid4() );

					// Limit to the first instance. Second instance will now be unique, or will be picked up on the next block in the loop.
					$post_content = preg_replace( '/' . preg_quote( $search, '/' ) . '/', $replace, $post_content, 1 );

					// Mark changed.
					$this->content_changed = true;
				}
			}
		}

		/**
		 * Filters post_content before saving to db to allow additional re-writing based on known PWP blocks.
		 *
		 * @since 2.6.0
		 *
		 * @param string  $post_content Current post content, sanitized and processed (by WP) but unslashed.
		 * @param array[] $pwp_blocks   All found blocks that have been Personalized.
		 * @return string
		 */
		$post_content = apply_filters( 'personalizewp_pre_blocks_update_post_content', $post_content, $this->pwp_blocks );

		$hash = md5( $post_content );
		// Check if anything has changed.
		if ( $hash !== $this->content_hash ) {
			// Return the content re-slashed.
			$data['post_content'] = wp_slash( $post_content );

			$this->content_changed = true;
			$this->content_hash    = $hash;
		}
		unset( $hash );

		return $data;
	}

	/**
	 * Triggers on update of a post to post-process any PWP blocks.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public function update_block_mappings( $post_id, $post, $update ) {

		// Double verify if this is an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Double check, skip revisions.
		if ( 'revision' === $post->post_type ) {
			return;
		}

		if ( empty( $post->post_content ) ) {
			// Nothing to do.
			return;
		}

		if ( $this->content_changed ) {
			// Re-parse content to get the correct set of blocks
			$this->pwp_blocks = $this->find_all_blocks( parse_blocks( $post->post_content ) );
		}

		$rules_blocks  = [];
		$save_mappings = [];
		foreach ( $this->pwp_blocks as $block ) {

			$post_ref                       = $post_id;
			$map_type                       = 'block-editor'; // Assume normal block editor.
			list( 'blockID' => $block_ref ) = $block['attrs']['personalizewp'];

			// Detect WP inbuilt CPTs indicating use of the Site Editor.
			if ( in_array( $post->post_type, [ 'wp_template', 'wp_template_part' ], true ) ) {
				$map_type = 'site-editor';
			}

			/**
			 * Filters the mappings used to store the blocks, allowing different mapping types to trigger.
			 *
			 * @since 2.6.0
			 *
			 * @param array[]  $save_mappings {
			 *     Array of block maps, keyed by 'block_ref', 'post_ref', 'map_type'. {
			 *
			 *     @type array ...$0 {
			 *         @type string $block_ref  Block ID.
			 *         @type string $post_ref   Post ID/reference.
			 *         @type string $map_type   Map type to track back to original block content.
			 *     }
			 * }
			 * @param \WP_Post $post       The original Post
			 * @param array[]  $pwp_blocks Processed blocks that have personalizewp data.
			 */
			$save_mappings[] = apply_filters(
				'personalizewp_update_block_mappings',
				compact( 'block_ref', 'post_ref', 'map_type' ),
				$post,
				$this->pwp_blocks
			);

			// Capture rules used for Active Blocks listings.
			$rules_blocks = array_merge( $rules_blocks, $this->expand_block_to_active_rules( $block, $post_id ) );
		}

		// Post has been "inserted"/"updated", potentially already has "active blocks"
		// 1. Get all "active rules" blocks for the post
		// 2. Compare against all new blocks via...
		// 2b. different count of blocks, or different block key hash
		// 3. Delete all existing db entries and recreate.
		$hash_map            = [];
		$known_active_blocks = \PersonalizeWP_Block::in_post( $post_id );

		// Expand all the known blocks with active rules to the same format as '$rules_blocks' above.
		if ( ! empty( $known_active_blocks ) ) {
			foreach ( $known_active_blocks as $block ) {
				// Use hashed key to allow checking on uniqueness (which should occur on block ref alone).
				$key              = md5( $post_id . '|' . $block->rule_id . '|' . $block->block_ref );
				$hash_map[ $key ] = $block;
			}
		}

		// Compare the currently known active rule blocks to the new active rule blocks.
		$dirty = false;
		// Quick check on number of active rule blocks, saves a loop.
		if ( count( $hash_map ) !== count( $rules_blocks ) ) {
			$dirty = true;
		} elseif ( ! empty( $rules_blocks ) ) {
			// Check if 'new' block already exists.
			foreach ( $rules_blocks as $key => $block ) {
				if ( empty( $hash_map[ $key ] ) ) {
					// Break on first block 'changed'.
					$dirty = true;
					break;
				}
			}
		}

		// This should ensure we only update when required.
		if ( $dirty ) {
			// Delete all existing active rule blocks, and especially clear the associated cache.
			$this->delete_active_rule_blocks( $post_id );

			// And simply re-insert, which requires a call to each Block object.
			foreach ( $rules_blocks as $block_rule ) {
				$block_rule->save();
			}
		}

		// For the new block mappings for the frontend.
		$block_mappings = Block_Mappings::instance();
		// Simply delete all existing block mappings.
		$block_mappings->delete_post_mappings( $post_id );
		// Then save all the new mappings.
		$block_mappings->save_mappings( $save_mappings );
	}

	/**
	 * Triggers on deletion of Post to delete its' block mappings.
	 *
	 * @since 2.6.0
	 *
	 * @param int $post_id ID of Post
	 */
	public function delete_block_mappings( $post_id ) {

		// Post has been fully deleted, delete all block mappings with same post_id.
		Block_Mappings::instance()->delete_post_mappings( $post_id );
	}

	/**
	 * Triggers on deletion of Post to delete any stored active rules blocks.
	 *
	 * @since 2.6.0
	 *
	 * @param int $post_id ID of Post
	 */
	public function delete_active_rule_blocks( $post_id ) {

		// Post has been fully deleted, delete all "active blocks" with same post_id.
		return \PersonalizeWP_Block::delete_blocks( $post_id );
	}

	/**
	 * Filter array of WP Blocks (such as `parse_blocks($post_content)`), returning all those with a valid 'personalizewp' attribute.
	 *
	 * @param array $blocks Array of WP Blocks
	 *
	 * @return array[] {
	 *     Array of block structures, that are valid PWP only, flattened to top level regardless of innerBlocks depth.
	 *
	 *     @type array ...$0 {
	 *         An associative array of a single parsed block object. See WP_Block_Parser_Block.
	 *
	 *         @type string   $blockName    Name of block.
	 *         @type array    $attrs        Attributes from block comment delimiters.
	 *         @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
	 *                                      have the same structure as this one.
	 *         @type string   $innerHTML    HTML from inside block comment delimiters.
	 *         @type array    $innerContent List of string fragments and null markers where
	 *                                      inner blocks were found.
	 *     }
	 * }
	 */
	protected function find_all_blocks( $blocks ) {

		$found = [];
		// loop through block array to identify block
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['attrs']['personalizewp']['blockID'] ) ) {
				$found[] = $block;
			}
			// check inner blocks
			if ( ! empty( $block['innerBlocks'] ) ) {
				$more = $this->find_all_blocks( $block['innerBlocks'] );
				if ( ! empty( $more ) ) {
					$found = array_merge( $found, $more );
				}
			}
		}
		return $found;
	}

	/**
	 * Expands a set of individual Blocks into multiple Rules for admin purposes
	 *
	 * @param array $block   WP Block
	 * @param int   $post_id ID of the current Post/CPT
	 *
	 * @return array[] {
	 *     Associative array of \PersonalizeWP_Block objects, keyed by a hash of the post ID, rule ID and block ref/id.
	 *
	 *     @type \PersonalizeWP_Block
	 * }
	 */
	protected function expand_block_to_active_rules( $block, $post_id ) {

		$active_rules = [];
		if ( empty( $block['attrs']['personalizewp']['rules'] ) ) {
			return $active_rules;
		}
		// Sanitise Rules to ensure uniqueness and no non-values.
		$rules = array_unique( array_filter( $block['attrs']['personalizewp']['rules'] ) );

		foreach ( $rules as $rule ) {
			$key = md5( $post_id . '|' . $rule . '|' . $block['attrs']['personalizewp']['blockID'] );

			if ( ! empty( $active_rules[ $key ] ) ) {
				continue;
				// Shouldn't get duplicate blocks. Fail better?
				// return new \WP_Error(
				// 'block_key_collision',
				// esc_html__( 'Duplicate blocks, collision detected.', 'personalizewp' ),
				// array( 'status' => 400 )
				// );
			}

			$active_rules[ $key ] = new \PersonalizeWP_Block(
				[
					'block_ref' => $block['attrs']['personalizewp']['blockID'],
					'rule_id'   => $rule,
					'name'      => $block['blockName'],
					'post_id'   => $post_id,
				]
			);
		}

		return $active_rules;
	}
}
