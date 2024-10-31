<?php
/**
 * The Block rendering functionality of the plugin.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 */

namespace PersonalizeWP;

use PersonalizeWP\Traits\SingletonTrait;
use WP_Block;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Handles blocks with conditions, replacing with placeholders when necessary, and rendering original block content when required.
 */
class Block_Renderer {

	use SingletonTrait;

	/**
	 * Used to track the current Post ID, for legacy <wp-dxp> placeholders.
	 *
	 * @since 2.6.0
	 *
	 * @var int
	 */
	private $post_id = null;

	/**
	 * Runtime rule object cache
	 *
	 * @since 1.1.0
	 * @var   array    $rules    Array of rules objects
	 */
	private $rules = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.6.0
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup public hooks
	 *
	 * @since 2.6.0
	 *
	 * @return void
	 */
	public function setup() {

		add_filter( 'register_block_type_args', [ $this, 'add_pwp_block_args' ], 10, 2 );

		// Note: This function runs way too early to detect the type of request, which we need to not replace blocks.
		// A quirk of WordPress is that the REST API runs from the `parse_request` action but immediately dies.
		// By delaying the Block_Renderer filters to an action later than that, means we only change non-REST based calls.
		add_action(
			'wp',
			function () {
				// Note: Action 'wp' only runs when the request isn't a REST api request.
				add_filter( 'pre_render_block', [ $this, 'track_pattern_post_id' ], 10, 2 );
				add_filter( 'render_block', [ $this, 'maybe_render_placeholder' ], 100, 3 );
			}
		);
	}

	/**
	 * Add the 'personalizewp' attribute, where our rules are stored, to dynamic blocks.
	 *
	 * We normally add the block attributes in the browser's JS env only, but many
	 * blocks use a ServerSideRender dynamic preview, so the PHP env needs
	 * to know about our new attribute(s), too.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings  Array of settings for registering a block type.
	 * @param string $name      Block type name including namespace.
	 */
	public function add_pwp_block_args( $settings, $name ) {

		// Only add to blocks that already have attributes.
		if ( empty( $settings['attributes'] ) ) {
			return $settings;
		}

		/** This is necessary for `/wp-json/wp/v2/block-renderer` REST endpoint to not throw `rest_additional_properties_forbidden`. */
		$settings['attributes']['personalizewp'] = [
			'type' => 'object',
		];

		/**
		 * Filter PersonalizeWP added Block Supported Attributes for the Block Editor
		 *
		 * @param array $settings Block Type Args
		 */
		return apply_filters( 'personalizewp_register_block_type_args', $settings );
	}

	/**
	 * Track the beginning of a core/block pattern block, as it's a different Post ID than where the pattern is used.
	 * Only used when displaying a legacy <wp-dxp> placeholder.
	 *
	 * @since 2.3.0
	 *
	 * @uses pre_render_block filter as an action to track the beginning of a pattern block
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string
	 */
	public function track_pattern_post_id( $block_content, $block ) {

		if ( ! empty( $block['blockName'] ) && 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
			// Store this for any blocks within the pattern as `get_the_ID()` will return the incorrect Post ID.
			$this->post_id = (int) $block['attrs']['ref'];
		}

		// Always return on a filter.
		return $block_content;
	}

	/**
	 * Filters all WordPress blocks to switch for a Placeholder, based on PersonalizeWP Rules. Wraps check for DXP Rules.
	 *
	 * @uses render_block filter
	 *
	 * @param string    $block_content The block content.
	 * @param array     $block         The full block, including name and attributes.
	 * @param \WP_Block $instance      The block instance.
	 * @return string
	 */
	public function maybe_render_placeholder( $block_content, $block, $instance ) {

		// WordPress works inside out with blocks, so at this point a core/block pattern has finished
		// rendering all of its content, so we can safely reset to use the normal post_id below.
		if ( ! empty( $block['blockName'] ) && 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
			$this->post_id = null;
		}

		// Quick check on format, not a PWP block, new or legacy.
		if ( empty( $block['attrs']['personalizewp']['blockID'] ) && empty( $block['attrs']['wpDxpId'] ) ) {
			return $block_content;
		}

		// On legacy format shift to that placeholder logic.
		if ( ! empty( $block['attrs']['wpDxpId'] ) ) {
			return $this->maybe_render_legacy_placeholder( $block_content, $block, $instance );
		}

		$pwp_attrs = $block['attrs']['personalizewp'];

		// Opt-in, assume the block will render its normal content instead of the Placeholder.
		$display_placeholder = false;

		// Check for existence of the Rules attribute to switch to displaying the placeholder.
		if ( ! empty( $pwp_attrs['rules'] ) ) {
			$display_placeholder = true;
		}

		/**
		 * Filter the displaying of the <pwp-block> placeholder.
		 *
		 * @since 2.6.0 Changed to only apply to non-legacy placeholders
		 *
		 * @param bool  $display_placeholder  The current status of placeholder display.
		 * @param array $block                The full block, including name and attributes.
		 */
		$display_placeholder = (bool) apply_filters( 'personalizewp_block_render_display_placeholder', $display_placeholder, $block );
		if ( ! $display_placeholder ) {
			return $block_content;
		}

		// At this point we know we are showing a placeholder.

		$placeholder_attributes = [];
		// Some Rules like to add extra attributes to the placeholder, e.g. to handle time based hiding/showing.
		if ( ! empty( $pwp_attrs['rules'] ) ) {

			// Is the block meant to hide or show initially?
			$block_action = ! empty( $pwp_attrs['action'] ) ? 'show' : $pwp_attrs['action'];

			// Some Rules (e.g. time based) want to add extra attributes to the placeholder based on the action.
			$rules = $this->get_rules( $pwp_attrs['rules'] );
			foreach ( $rules as $rule ) {
				$tag_attrs = $rule->tagAttributes( $block_action );
				foreach ( $tag_attrs as $k => $v ) {
					$placeholder_attributes[ $k ] = $v;
				}
			}
		}

		/**
		 * Filter the placeholders' HTML attributes.
		 *
		 * @since 2.6.0 Changed to only apply to non-legacy placeholders
		 *
		 * @param string[] $placeholder_attributes  An array of added HTML attributes for the placeholder.
		 * @param array    $block                   The full block, including name and attributes.
		 */
		$placeholder_attributes = apply_filters( 'personalizewp_block_render_placeholder_attributes', $placeholder_attributes, $block );

		$extra_attributes = '';
		foreach ( $placeholder_attributes as $name => $value ) {
			$extra_attributes .= sprintf(
				' %1$s="%2$s" ',
				esc_html( $name ),
				esc_attr( $value )
			);
		}

		/**
		 * Filter the complete <pwp-block> placeholder that is returned instead of the original block content.
		 *
		 * @since 2.6.0 Changed to only apply to non-legacy placeholders
		 * @since 2.2.0
		 *
		 * @param string $placeholder  The HTML web component placeholder.
		 * @param array  $block        The full block, including name and attributes.
		 */
		$placeholder = apply_filters(
			'personalizewp_block_render_placeholder',
			sprintf(
				'<pwp-block block-id="%1$s" %2$s></pwp-block>',
				$block['attrs']['personalizewp']['blockID'],
				$extra_attributes // Already escaped above
			),
			$block
		);

		// Return the placeholder, overwriting the original block content.
		return $placeholder;
	}

	/**
	 * Filters all WordPress blocks to switch for a Placeholder, based on legacy PersonalizeWP Rules.
	 *
	 * @uses render_block filter
	 *
	 * @since 2.6.0
	 *
	 * @param string    $block_content The block content.
	 * @param array     $block         The full block, including name and attributes.
	 * @param \WP_Block $instance      The block instance.
	 * @return string
	 */
	protected function maybe_render_legacy_placeholder( $block_content, $block, $instance ) {

		// Skip basic checks already done in main placeholder function.

		// Opt-in, assume the block will render its normal content instead of the Placeholder.
		$display_placeholder = false;

		// Check for existence of the Rules attribute to switch to displaying the placeholder.
		if ( ! empty( $block['attrs']['wpDxpRule'] ) ) {
			$display_placeholder = true;
		}

		/**
		 * Filter the displaying of the <wp-dxp> placeholder.
		 *
		 * @since 2.6.0
		 *
		 * @param bool  $display_placeholder  The current status of placeholder display.
		 * @param array $block                The full block, including name and attributes.
		 */
		$display_placeholder = (bool) apply_filters( 'personalizewp_block_render_legacy_display_placeholder', $display_placeholder, $block );
		if ( ! $display_placeholder ) {
			return $block_content;
		}

		// At this point we know we are showing a placeholder.

		$placeholder_attr = [];
		// Some Rules like to add extra attributes to the placeholder, e.g. to handle time based hiding/showing.
		if ( ! empty( $block['attrs']['wpDxpRule'] ) ) {
			// Is the block meant to hide or show initially?
			$block_action = empty( $block['attrs']['wpDxpAction'] ) ? 'show' : $block['attrs']['wpDxpAction'];
			// As some Rules want to add extra attributes to the placeholder based on that action.
			$rules = $this->get_rules( $block['attrs']['wpDxpRule'] );
			foreach ( $rules as $rule ) {
				$tag_attrs = $rule->tagAttributes( $block_action );
				foreach ( $tag_attrs as $k => $v ) {
					$placeholder_attr[ $k ] = $v;
				}
			}
		}

		/**
		 * Filter the placeholders' HTML attributes.
		 *
		 * @since 2.6.0
		 *
		 * @param string[] $placeholder_attr  An array of added HTML attributes for the placeholder.
		 * @param array    $block             The full block, including name and attributes.
		 */
		$placeholder_attr = apply_filters( 'personalizewp_block_render_legacy_placeholder_attributes', $placeholder_attr, $block );

		$_post_id    = ! empty( $this->post_id ) ? $this->post_id : get_the_ID();
		$extra_attrs = '';
		foreach ( $placeholder_attr as $name => $value ) {
			$extra_attrs .= sprintf(
				' %1$s="%2$s" ',
				esc_html( $name ),
				esc_attr( $value )
			);
		}

		/**
		 * Filter the complete PWP placeholder that is returned instead of the original block content.
		 *
		 * @since 2.6.0
		 *
		 * @param string $placeholder  The HTML web component placeholder.
		 * @param int    $_post_id     ID of the post the block is within.
		 * @param array  $block        The full block, including name and attributes.
		 */
		$placeholder = apply_filters(
			'personalizewp_block_render_legacy_placeholder',
			sprintf(
				'<wp-dxp post-id="%1$s" block-id="%2$s" %3$s></wp-dxp>',
				(int) $_post_id,
				$block['attrs']['wpDxpId'],
				$extra_attrs // Already escaped above
			),
			(int) $_post_id,
			$block
		);

		// Return the placeholder, overwriting the original block content.
		return $placeholder;
	}

	/**
	 * Checks a single parsed block object validating against PWP conditions. A non-null value causes the block to not display.
	 *
	 * @uses pre_render_block
	 *
	 * @param string|null    $pre_render   The pre-rendered content. Default null.
	 * @param array          $parsed_block The block being rendered.
	 * @param \WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 *
	 * @return bool True if the block should be rendered, false otherwise
	 */
	public function maybe_render_block( $pre_render, $parsed_block, $parent_block ) {

		if ( empty( $parsed_block['attrs']['personalizewp'] ) ) {
			return $pre_render;
		}

		// Set to true initially and change based on Rules
		$display_block = true;

		$pwp_attrs = $parsed_block['attrs']['personalizewp'];

		if ( ! empty( $pwp_attrs['rules'] ) ) {
			$action = empty( $pwp_attrs['action'] ) ? 'show' : $pwp_attrs['action'];
			$rules  = $this->get_rules( $pwp_attrs['rules'] );

			foreach ( $rules as $rule ) {
				// If Rule's not found or is not usable due to missing dependencies then don't show content
				if ( ! $rule || ! $rule->is_usable ) {
					$display_block = false;
					break; // Immediately stop other rules
				}

				$matched_conditions = $rule->conditionsMatched( $action );

				switch ( true ) {
					case 'show' === $action && $matched_conditions:
					case 'hide' === $action && ! $matched_conditions:
						// Do nothing, will show block
						break;
					case 'hide' === $action && $matched_conditions:
					case 'show' === $action && ! $matched_conditions:
						$display_block = false;
						break 2; // Immediately stop other rules
				}
			}
		}

		/**
		 * Filters on the block output, should it be displayed or not.
		 *
		 * @param bool  $display_block  The current status of placeholder display.
		 * @param array $block          The full block, including name and attributes.
		 */
		$display_block = (bool) apply_filters( 'personalizewp_should_render_block', $display_block, $parsed_block, $GLOBALS['PERSONALIZEWP_PARAMS'] );
		if ( ! $display_block ) {
			return ''; // Anything non-null causes the block to not display
		}

		return $pre_render;
	}

	/**
	 * Checks a single parsed block object validating against DXP conditions. A non-null value causes the block to not display.
	 *
	 * @uses pre_render_block
	 *
	 * @since 2.6.0
	 *
	 * @param string|null    $pre_render   The pre-rendered content. Default null.
	 * @param array          $parsed_block The block being rendered.
	 * @param \WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 *
	 * @return bool True if the block should be rendered, false otherwise
	 */
	public function maybe_render_legacy_block( $pre_render, $parsed_block, $parent_block ) {

		// Set to true initially and change based on Rules
		$display_block = true;

		if ( ! empty( $parsed_block['attrs']['wpDxpRule'] ) ) {
			$action = empty( $parsed_block['attrs']['wpDxpAction'] ) ? 'show' : $parsed_block['attrs']['wpDxpAction'];
			$rules  = $this->get_rules( $parsed_block['attrs']['wpDxpRule'] );

			foreach ( $rules as $rule ) {
				// If Rule's not found or is not usable due to missing dependencies then don't show content
				if ( ! $rule || ! $rule->is_usable ) {
					$display_block = false;
					break; // Immediately stop other rules
				}

				$matched_conditions = $rule->conditionsMatched( $action );

				switch ( true ) {
					case 'show' === $action && $matched_conditions:
					case 'hide' === $action && ! $matched_conditions:
						// Do nothing, will show block
						break;
					case 'hide' === $action && $matched_conditions:
					case 'show' === $action && ! $matched_conditions:
						$display_block = false;
						break 2; // Immediately stop other rules
				}
			}
		}

		/**
		 * Filters on the block output, should it be displayed or not.
		 *
		 * @since 2.6.0
		 *
		 * @param bool  $display_block  The current status of placeholder display.
		 * @param array $block          The full block, including name and attributes.
		 */
		$display_block = (bool) apply_filters( 'personalizewp_should_render_legacy_block', $display_block, $parsed_block, $GLOBALS['PERSONALIZEWP_PARAMS'] );
		if ( ! $display_block ) {
			return ''; // Anything non-null causes the block to not display
		}

		return $pre_render;
	}

	/**
	 * Renders a single parsed block object, wrapper for WP native render_block()
	 *
	 * Similar to do_blocks() but for a single block.
	 *
	 * @param \WP_Block $block_instance The block instance.
	 *
	 * @return string HTML of rendered block
	 */
	public function render_block( $block_instance ) {

		$block_content = \render_block( $block_instance );

		// If there are blocks in this content, we shouldn't run wpautop() on it.
		// Fixes embeded scripts having extra paragraphs etc
		$priority = has_filter( 'the_content', 'wpautop' );
		if ( false !== $priority ) {
			remove_filter( 'the_content', 'wpautop', $priority );
		}

		// Ensure normal filters are run for embeds etc. but remove excess whitespace.
		$block_content = trim( apply_filters( 'the_content', $block_content ) );
		return $block_content;
	}

	/**
	 * Gets all Rule db entries
	 *
	 * @since 2.6.0 Changed param to accept array and string
	 *
	 * @param string|array $ids Rule IDs to retrieve
	 *
	 * @return array
	 */
	private function get_rules( $ids ) {

		if ( ! is_array( $ids ) && is_string( $ids ) ) {
			$ids = explode( ',', $ids );
		}
		// Sanitise all.
		$ids = array_unique( array_filter( $ids ) );
		if ( empty( $ids ) ) {
			return [];
		}

		// Check for any rule IDs not already initialised in runtime cache
		$missing_ids = [];
		foreach ( $ids as $id ) {
			if ( ! isset( $this->rules[ $id ] ) ) {
				$missing_ids[] = $id;
			}
		}

		// Prime the rule cache with all required rules.
		if ( ! empty( $missing_ids ) ) {
			$fresh_rules = \PersonalizeWP_Rule::findAll( implode( ',', $missing_ids ) );
			if ( $fresh_rules ) {
				foreach ( $fresh_rules as $fresh_rule ) {
					if ( $fresh_rule ) {
						$this->rules[ $fresh_rule->id ] = $fresh_rule;
					}
				}
			}
		}

		// Get the sub-set of rules requested.
		$rules = [];
		foreach ( $ids as $id ) {
			if ( isset( $this->rules[ $id ] ) ) {
				$rules[] = $this->rules[ $id ];
			}
		}

		return $rules;
	}
}
