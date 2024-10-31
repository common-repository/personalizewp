<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Public
 */

namespace PersonalizeWP;

use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * The public-facing functionality of the plugin.
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/public
 */
class PublicFacing {

	use SingletonTrait;

	/**
	 * Runtime cache of posts and their processed blocks.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $posts    Array of post objects
	 */
	private $posts = [];

	/**
	 * Holds Instance of plugin object
	 *
	 * @var PersonalizeWP
	 */
	private $plugin;

	/**
	 * The namespace for the API endpoints, without versioning.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	protected $namespace = 'personalizewp/';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Switch to single plugin param
	 * @since 2.5.0 Remove param
	 */
	public function __construct() {
		$this->plugin = \personalizewp();

		$this->setup();
	}

	/**
	 * Setup public hooks
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function setup() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if ( is_admin() ) {
			return;
		}

		$handle  = $this->plugin->get_personalizewp();
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin->get_version();
		wp_enqueue_style( $handle, $this->plugin->plugin_url( "public/css/pwp{$suffix}.css" ), array(), $version );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( is_admin() ) {
			return;
		}

		$handle     = $this->plugin->get_personalizewp();
		$properties = require $this->plugin->plugin_path( 'public/js/pwp.asset.php' );
		$js_version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $properties['version'] : $this->plugin->get_version();
		wp_enqueue_script( $handle, $this->plugin->plugin_url( 'public/js/pwp.js' ), $properties['dependencies'], $js_version, [ 'strategy' => 'defer' ] );

		wp_localize_script(
			$this->plugin->get_personalizewp(),
			'pwpSettings',
			array(
				'root'      => sanitize_url( get_rest_url() ), // phpcs:ignore WordPress.WP.DeprecatedFunctions.sanitize_url -- 5.9 un-deprecated this
				'nonce'     => wp_create_nonce( 'wp_rest' ), // Required for checking on logged in status
				'delayInit' => (bool) $this->plugin->get_setting( 'general_delay_initialisation' ),
			)
		);

		// Support legacy blocks, hardcoded dependancy on main script
		$properties = require $this->plugin->plugin_path( 'public/js/wp-dxp.asset.php' );
		$js_version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $properties['version'] : $this->plugin->get_version();
		wp_enqueue_script( "{$handle}-legacy", $this->plugin->plugin_url( 'public/js/wp-dxp.js' ), array_merge( [ $handle ], $properties['dependencies'] ), $js_version, [ 'strategy' => 'defer' ] );
	}

	/**
	 * Register API routes for blocks
	 */
	public function register_endpoints() {
		register_rest_route(
			"{$this->namespace}v2",
			'/blocks',
			[
				'methods'             => \WP_REST_Server::CREATABLE, // Not actually creating, but JS Fetch has a body for params which requires POST
				'callback'            => [ $this, 'get_blocks' ],
				// Register our expected args to validate and sanitise.
				'args'                => array_merge(
					[
						'blocks' => [
							'required'          => true,
							'type'              => 'array',
							'items'             => array(
								'type' => 'string',
							),
							'validate_callback' => [ $this, 'validate_non_empty_array' ],
						],
					],
					$this->get_user_data_args()
				),
				'permission_callback' => '__return_true',
			]
		);

		// Support legacy route with slightly different args
		register_rest_route(
			"{$this->namespace}v1",
			'/blocks',
			[
				'methods'             => \WP_REST_Server::CREATABLE, // Not actually creating, but JS Fetch has a body for params which requires POST
				'callback'            => [ $this, 'get_legacy_blocks' ],
				// Register our expected args to validate and sanitise.
				'args'                => array_merge(
					[
						'blocks' => [
							'required'          => true,
							'type'              => 'array',
							'items'             => array(
								'type'                 => 'object',
								'properties'           => array(
									'block_id' => array(
										'type'     => 'string',
										'required' => true,
									),
									'post_id'  => array(
										'type' => 'string',
									),
								),
								'additionalProperties' => false, // Disallow extra
							),
							'validate_callback' => [ $this, 'validate_non_empty_array' ],
						],
					],
					$this->get_user_data_args()
				),
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Retrieves an array of endpoint arguments for user Data.
	 *
	 * Used within all /blocks/ requests.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	protected function get_user_data_args() {

		// Ordered alphabetically
		return [
			'currentTime'        => [
				'required' => true,
				'type'     => 'string',
				'pattern'  => '[0-9]{2}:[0-9]{2}:[0-9]{2}',
				// Validation/Sanitizing automatic as type/pattern set
			],
			'currentTimestamp'   => [
				'required' => true,
				'type'     => 'string',
				'format'   => 'date-time',
				// Validation/Sanitizing automatic as type/format set
			],
			'daysSinceLastVisit' => [
				'required'          => true,
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			],
			'deviceType'         => [
				'required' => true,
				'type'     => 'array',
				'items'    => array(
					'type' => 'string',
					'enum' => array( 'mobile', 'tablet', 'desktop', 'windows', 'android', 'ios', '' ),
				),
				// Validation/Sanitizing automatic as type/enum set
			],
			'isReturningVisitor' => [
				'required' => true,
				'type'     => 'boolean',
				// Validation/Sanitizing automatic as type set
			],
			'location'           => [
				'required'          => true,
				'type'              => 'string',
				'format'            => 'uri',
				// Ensure referrer isn't empty, default validation doesn't check for non-empty
				'validate_callback' => [ $this, 'validate_non_empty_string' ],
				'sanitize_callback' => function ( $value, $request, $key ) {
					$value = sanitize_url( $value ); // phpcs:ignore WordPress.WP.DeprecatedFunctions.sanitize_url -- 5.9 un-deprecated this
					// Sanitize and remove any "admin" based URL.
					if ( str_contains( $value, str_replace( array( 'https:', 'http:' ), '', admin_url( '/' ) ) ) ) {
						$value = '';
					}
					return $value;
				},
			],
			'referrerURL'        => [
				'required'          => true,
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'sanitize_url',
			],
			'timeOfDay'          => [
				'required' => true,
				'type'     => 'string',
				'enum'     => array( 'nighttime', 'morning', 'afternoon', 'evening' ),
				// Validation/Sanitizing automatic as type/enum set
			],
			'uid'                => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'urlQueryString'     => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Validate the arg is a string and not empty. rest_validate_request_arg() only does the former.
	 *
	 * @param  mixed            $value   Value of the argument.
	 * @param  \WP_REST_Request $request The current request object.
	 * @param  string           $param   Key of the parameter.
	 * @return \WP_Error|boolean
	 */
	public function validate_non_empty_string( $value, $request, $param ) {

		if ( ! is_string( $value ) || empty( $value ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Validate the arg is an array and not empty.
	 *
	 * @param  mixed            $value   Value of the argument.
	 * @param  \WP_REST_Request $request The current request object.
	 * @param  string           $param   Key of the parameter.
	 * @return \WP_Error|boolean
	 */
	public function validate_non_empty_array( $value, $request, $param ) {

		if ( ! is_array( $value ) || empty( $value ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Handles returning relevant blocks request
	 *
	 * @since 2.6.0 Updated for v2 request
	 *
	 * @param \WP_REST_Request $request api request
	 *
	 * @return object|array
	 */
	public function get_blocks( \WP_REST_Request $request ) {
		global $post;

		$data = [];

		$params = $request->get_json_params();
		$this->setup_params_for_rule_conditions( $params );

		// Track processed mappings so as not to duplicate work.
		$processed = [];
		$mappings  = Block_Mappings::instance()->get_block_mappings( $params['blocks'] );

		$renderer = Block_Renderer::instance();
		// We turn off the change of rendering from normal block content to placeholders.
		remove_filter( 'render_block', [ $renderer, 'maybe_render_placeholder' ], 100 );
		// Instead we filter the blocks before render, validating against PersonalizeWP args, bypassing rendering if invalid.
		add_filter( 'pre_render_block', [ $renderer, 'maybe_render_block' ], 100, 3 );

		// loop through requested blocks, get references from mappings and fetch original block content.
		foreach ( $params['blocks'] as $index => $block_ref ) {

			// Must always return the same number of results as refs.
			$data[ $index ] = null;
			// Check for valid block ref and which was correctly processed.
			if ( empty( $block_ref ) || ! isset( $mappings[ $block_ref ] ) ) {
				continue;
			}
			if ( isset( $processed[ $block_ref ] ) ) {
				// Duplicate reference set, repeat return.
				$data[ $index ] = $processed[ $block_ref ];
			}

			$map = $mappings[ $block_ref ];

			// Switch between code paths depending on mapping type. Used to find the correct
			// "post" and its' "post_content" to return the original content for the block.
			switch ( $map['map_type'] ) {
				// The core editor for Pages, Posts, and CPTs.
				case 'block-editor':
					$pwp_post = $this->get_post( $map['post_ref'] );
					// do we have blocks for this post?
					if ( ! empty( $pwp_post['blocks'] ) ) {

						// Overwrite the global the WP would normally setup, so that rendering of blocks works correctly.
						$post = $pwp_post['post']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

						// do we have a matching block? which might have innerBlocks
						$block = $this->find_block( $map['block_ref'], $pwp_post['blocks'] );
						if ( ! is_null( $block ) ) {
							// Attempt to render the block, filtering to check for conditions
							$data[ $index ] = $renderer->render_block( $block );
						}
					}
					break;

				// The core editor used for block templates.
				case 'site-editor':
					$pwp_post = $this->get_template( $map['post_ref'] );
					// do we have blocks for this post?
					if ( ! empty( $pwp_post['blocks'] ) ) {

						// Overwrite the global the WP would normally setup, so that rendering of blocks works correctly.
						$post = $pwp_post['post']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

						// do we have a matching block? which might have innerBlocks
						$block = $this->find_block( $map['block_ref'], $pwp_post['blocks'] );
						if ( ! is_null( $block ) ) {
							// Attempt to render the block, filtering to check for conditions
							$data[ $index ] = $renderer->render_block( $block );
						}
					}
					break;

				default:
					$block_content = '';
					/**
					 * Filters the default callback for processing unknown block mappings.
					 *
					 * Allows support for 3rd party plugins that store the post_content' block elsewhere.
					 *
					 * @since 2.6.0
					 *
					 * @param string|false $callback A callable function to return the original block content
					 * @param array        $map      Block/post mapping references
					 *
					 * @return string HTML of block content if valid to show, empty string otherwise
					 */
					$callback = apply_filters( 'personalizewp_get_blocks_mapping', false, $map );
					if ( ! $callback && is_callable( $callback ) ) {
						// Callback must use the mappings to work out if the block should be
						$block_content = $callback( $map['block_ref'], $map['post_ref'] );
					}
					$data[ $index ] = $block_content;
					break;
			}
			// Track blocks rendered in case of duplicate references sent.
			$processed[ $block_ref ] = $data[ $index ];
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Handles returning relevant blocks request
	 *
	 * @param \WP_REST_Request $request api request
	 *
	 * @return object|array
	 */
	public function get_legacy_blocks( \WP_REST_Request $request ) {
		global $post;

		$data = [];

		$params = $request->get_json_params();
		$this->setup_params_for_rule_conditions( $params );

		$renderer = Block_Renderer::instance();
		// We turn off the change of rendering from normal block content to placeholders.
		remove_filter( 'render_block', [ $renderer, 'maybe_render_placeholder' ], 100 );
		// Instead we filter the blocks before render, validating against DXP args, bypassing rendering if invalid.
		add_filter( 'pre_render_block', [ $renderer, 'maybe_render_legacy_block' ], 100, 3 );

		// loop through blocks and fetch data
		foreach ( $params['blocks'] as $i => $b ) {

			$data[ $i ] = null;

			// do we have a block ID and a post ID?
			if ( ! empty( $b['block_id'] ) && ! empty( $b['post_id'] ) ) {

				$dxp_post = $this->get_post( $b['post_id'] );
				// do we have blocks for this post?
				if ( $dxp_post['blocks'] ) {

					// Overwrite the global the WP would normally setup, so that rendering of blocks works correctly.
					$post = $dxp_post['post']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

					// do we have a matching block? which might have innerBlocks
					$block = $this->find_legacy_block( $b['block_id'], $dxp_post['blocks'] );
					if ( ! is_null( $block ) ) {
						// Attempt to render the block, we filter to check for conditions
						$data[ $i ] = $renderer->render_block( $block );
					}
				}
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Maps the incoming REST params to the naming required for Rules
	 *
	 * @uses $GLOBALS['PERSONALIZEWP_PARAMS']
	 *
	 * @since 2.6.0
	 *
	 * @param array $params Array of params sent via REST request
	 *
	 * @return void
	 */
	protected function setup_params_for_rule_conditions( $params ) {
		/**
		 * List of PersonalizeWP GLOBAL options
		 *
		 * Mapping from the required names needed for Rule Conditions to the names that are sent via the REST/JS API
		 */
		$pwp_opts_mapping = array(
			'time_of_day'             => 'timeOfDay',
			'users_current_time'      => 'currentTime',
			'users_current_timestamp' => 'currentTimestamp',
			'returning_visitor'       => 'isReturningVisitor',
			'daysSinceLastVisit'      => 'daysSinceLastVisit',
			'users_device_type'       => 'deviceType',
			'url_query_string'        => 'urlQueryString',
			'referrer_url'            => 'referrerURL',
			'uid'                     => 'uid',
		);
		foreach ( $pwp_opts_mapping as $rule_name => $js_name ) {
			// All params have already been sanitized via the REST API via patterns, types, enums etc.
			$GLOBALS['PERSONALIZEWP_PARAMS'][ $rule_name ] = isset( $params[ $js_name ] ) ? $params[ $js_name ] : '';
		}

		// Overwrite the REST API with the JS location for those that dynamically adjust to using the "current" URL
		if ( ! empty( $params['location'] ) ) {
			$_SERVER['REQUEST_URI'] = sanitize_url( $params['location'] );  // phpcs:ignore WordPress.WP.DeprecatedFunctions.sanitize_url -- 5.9 un-deprecated this
		}
	}

	/**
	 * Retrieves a single block out of an array, matching the PersonalizeWP block reference.
	 *
	 * @since 2.6.0
	 *
	 * @param string $ref    Block reference to find
	 * @param array  $blocks Parsed block objects
	 *
	 * @return null|array Single parsed block object or null if not found
	 */
	private function find_block( $ref, $blocks ) {

		// loop through block array to identify block
		foreach ( $blocks as $b ) {

			// is THIS block the one we want?
			if ( ! empty( $b['attrs']['personalizewp']['blockID'] ) && $ref === $b['attrs']['personalizewp']['blockID'] ) {
				return $b;
			}

			// check inner blocks, do a callback
			$b = $this->find_block( $ref, $b['innerBlocks'] );
			if ( ! empty( $b ) ) {
				return $b;
			}
		}

		// block not found
		return null;
	}

	/**
	 * Retrieves a single block out of an array, matching the legacy PWP Block ID.
	 *
	 * @param int   $id     PWP Block ID to find
	 * @param array $blocks Parsed block objects
	 *
	 * @return null|array Single parsed block object or null if not found
	 */
	private function find_legacy_block( $id, $blocks ) {

		// loop through block array to identify block
		foreach ( $blocks as $b ) {

			// is THIS block the one we want?
			if ( ! empty( $b['attrs']['wpDxpId'] ) && $id === $b['attrs']['wpDxpId'] ) {
				return $b;
			}

			// check inner blocks, do a callback
			$b = $this->find_legacy_block( $id, $b['innerBlocks'] );
			if ( ! empty( $b ) ) {
				return $b;
			}
		}

		// block not found
		return null;
	}

	/**
	 * Retrieves post data and the posts' blocks given a block template name.
	 * Uses non-persistent cache for repeated uses of the same template.
	 *
	 * Used by the REST api only
	 *
	 * @since 2.6.0
	 *
	 * @param string $ref Template reference/name
	 *
	 * @return array {
	 *   @type \WP_Post $post   WP_Post object
	 *   @type array[]  $blocks Array of parsed block objects
	 * }
	 */
	private function get_template( $ref ) {
		global $post;

		// fetch and cache the post (in case of multiple blocks on same post) and parse post_content.
		if ( ! isset( $this->posts[ $ref ] ) ) {
			$templates           = get_block_templates( array( 'wp_id' => $ref ) );
			$this->posts[ $ref ] = [
				'post'   => is_array( $templates ) ? array_shift( $templates ) : null,
				'blocks' => null,
			];

			// Check that post exists.
			if ( $this->posts[ $ref ]['post'] ) {
				// Overwrite the global the WP would normally setup, so that rendering of blocks works correctly.
				$post   = $this->posts[ $ref ]['post']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$blocks = parse_blocks( $this->posts[ $ref ]['post']->content );

				// Store parsed blocks for future runtime calls.
				$this->posts[ $ref ]['blocks'] = $blocks;
			}
		}

		return $this->posts[ $ref ];
	}

	/**
	 * Retrieves post data and the posts' blocks given a post ID.
	 * Uses non-persistent cache for repeated uses of the same post.
	 *
	 * Used by the REST api only
	 *
	 * @param int $id ID of Post
	 *
	 * @return array {
	 *   @type \WP_Post $post   WP_Post object
	 *   @type array[]  $blocks Array of parsed block objects
	 * }
	 */
	private function get_post( $id ) {
		global $post;

		// fetch and cache the post (in case of multiple blocks on same post) and parse post_content.
		if ( ! isset( $this->posts[ $id ] ) ) {
			$this->posts[ $id ] = [
				'post'   => get_post( $id ),
				'blocks' => null,
			];

			// Check that post exists
			if ( $this->posts[ $id ]['post'] ) {
				// Overwrite the global the WP would normally setup, so that rendering of blocks works correctly.
				$post   = $this->posts[ $id ]['post']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$blocks = parse_blocks( $this->posts[ $id ]['post']->post_content );

				// Store parsed blocks for future runtime calls.
				$this->posts[ $id ]['blocks'] = $blocks;
			}
		}

		return $this->posts[ $id ];
	}
}
