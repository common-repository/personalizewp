<?php
/**
 * Defines Rest Settings API
 *
 * @link  https://personalizewp.com
 * @since 2.6.0
 *
 * @package PersonalizeWP/
 * @subpackage PersonalizeWP/Admin/REST
 */

namespace PersonalizeWP\Admin\REST;

use PersonalizeWP_Rule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Settings Controller class
 *
 * @extends  WP_REST_Controller
 */
class Settings_Controller extends \WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'personalizewp/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Registers the routes for the search controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'get_settings_permission_check' ],
				),
			)
		);
	}

	/**
	 * Checks if the request has access.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_settings_permission_check( $request ) {

		// Restrict endpoint to only users who have the edit_posts capability.
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		// This is an allow-list approach.
		return new \WP_Error( 'rest_forbidden', esc_html__( 'You can not view private data.', 'personalizewp' ), array( 'status' => 401 ) );
	}

	/**
	 * Gets a collection of settings and variables.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_settings( $request ) {

		$settings = array(
			'plugin_settings' => array(
				'version' => PERSONALIZEWP_VERSION,
			),
			'is_pro'          => personalizewp()->is_pro(),
			'rules'           => $this->get_all_rules(),
		);

		/**
		 * Filter PersonalizeWP REST settings for block editor JS.
		 *
		 * @since 2.6.0
		 *
		 * @param array            $settings  Base settings for PersonalizeWP
		 * @param \WP_REST_Request $request   REST Request
		 */
		$settings = apply_filters(
			'personalizewp_rest_settings',
			$settings,
			$request
		);

		return rest_ensure_response( $settings );
	}

	/**
	 * Retrieves all PersonalizeWP rules on the website.
	 *
	 * @return array
	 */
	public function get_all_rules() {

		$all_rules = PersonalizeWP_Rule::all();

		// Convert rules objects into simplier array
		$rules = [];
		foreach ( $all_rules as $rule ) {
			$rules[] = [
				'id'        => $rule->id,
				'name'      => $rule->name,
				'is_usable' => $rule->is_usable,
			];
		}

		return $rules;
	}

	/**
	 * Get the Settings schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			// Since WordPress 5.3, the schema can be cached in the $schema property.
			return $this->schema;
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'settings',
			'type'       => 'object',
			'properties' => array(
				'plugin_variables' => array(
					'description' => __( 'An array of plugin variables, such as version number.', 'personalizewp' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'is_pro'           => array(
					'description' => __( 'An indicator to determine if PersonalizeWP Pro is enabled.', 'personalizewp' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'rules'            => array(
					'description' => __( 'The available rules on the site.', 'personalizewp' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'user_roles'       => array(
					'description' => __( 'The available user roles on the site.', 'personalizewp' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filter the schema for the PersonalizeWP REST settings.
		 *
		 * @since 2.6.0
		 *
		 * @param array $schema Current json schema
		 */
		$this->schema = apply_filters(
			'personalizewp_rest_settings_schema',
			$schema
		);

		return $this->schema;
	}
}
