<?php
/**
 * Base Admin screen used for extending.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

// namespace PersonalizeWP\Admin;

use PersonalizeWP\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Base Admin
 */
class PersonalizeWP_Admin_Base_Page {

	use SingletonTrait;

	/**
	 * Stores any errors during form processing.
	 *
	 * @var array
	 */
	protected $validation_errors = [];

	/**
	 * Page title for the Admin page
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $page_title;

	/**
	 * Used to store the current action from the URL, shared between process/route actions
	 *
	 * @var string
	 */
	protected $current_action;

	/**
	 * Used to store the current processed form data, shared between process/route actions
	 *
	 * @var array
	 */
	protected $form_post = [];

	/**
	 * Process - typically used for processing POST data
	 */
	public function process() {}

	/**
	 * Route to the correct action within the page
	 */
	public function route() {}

	/**
	 * Display the shared Header for all PWP pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_title Page title
	 *
	 * @return void
	 */
	public function display_header( $page_title = '' ) {
		if ( empty( $page_title ) ) {
			$page_title = $this->page_title;
		}
		$page     = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : false;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- no site changes here.
		$base_url = plugins_url( '', __FILE__ );

		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/_other/header.php';
	}


	/**
	 * Display the shared footer for all PWP pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_footer() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/_other/footer.php';
	}

	/**
	 * Outputs an admin notice. Wrapper for wp_admin_notice().
	 *
	 * @since 2.0.0
	 *
	 * @param string $message The message to output.
	 * @param array  $args {
	 *     Optional. An array of arguments for the admin notice. Default empty array.
	 *
	 *     @type string   $type               Optional. The type of admin notice.
	 *                                        For example, 'error', 'success', 'warning', 'info'.
	 *                                        Default empty string.
	 *     @type bool     $dismissible        Optional. Whether the admin notice is dismissible. Default false.
	 *     @type string   $id                 Optional. The value of the admin notice's ID attribute. Default empty string.
	 *     @type string[] $additional_classes Optional. A string array of class names. Default empty array.
	 *     @type string[] $attributes         Optional. Additional attributes for the notice div. Default empty array.
	 *     @type bool     $paragraph_wrap     Optional. Whether to wrap the message in paragraph tags. Default true.
	 * }
	 *
	 * @return mixed
	 */
	protected function add_admin_notice( $message, $args = array() ) {

		// Use native WP functionality where possible. Since 6.4
		if ( function_exists( 'wp_admin_notice' ) ) {
			wp_admin_notice( $message, $args );
			return;
		}

		// Or revert to duplicating the code directly.
		$defaults = array(
			'type'               => '',
			'dismissible'        => false,
			'id'                 => '',
			'additional_classes' => array(),
			'attributes'         => array(),
			'paragraph_wrap'     => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$id         = '';
		$classes    = 'notice';
		$attributes = '';

		if ( is_string( $args['id'] ) ) {
			$trimmed_id = trim( $args['id'] );

			if ( '' !== $trimmed_id ) {
				$id = 'id="' . $trimmed_id . '" ';
			}
		}

		if ( is_string( $args['type'] ) ) {
			$type = trim( $args['type'] );

			if ( str_contains( $type, ' ' ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: %s: The "type" key. */
						esc_html__( 'The %s key must be a string without spaces.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Code from core WP for backwards compat
						'<code>type</code>'
					),
					'6.4.0'
				);
			}

			if ( '' !== $type ) {
				$classes .= ' notice-' . $type;
			}
		}

		if ( true === $args['dismissible'] ) {
			$classes .= ' is-dismissible';
		}

		if ( is_array( $args['additional_classes'] ) && ! empty( $args['additional_classes'] ) ) {
			$classes .= ' ' . implode( ' ', $args['additional_classes'] );
		}

		if ( is_array( $args['attributes'] ) && ! empty( $args['attributes'] ) ) {
			$attributes = '';
			foreach ( $args['attributes'] as $attr => $val ) {
				if ( is_bool( $val ) ) {
					$attributes .= $val ? ' ' . $attr : '';
				} elseif ( is_int( $attr ) ) {
					$attributes .= ' ' . esc_attr( trim( $val ) );
				} elseif ( $val ) {
					$attributes .= ' ' . $attr . '="' . esc_attr( trim( $val ) ) . '"';
				}
			}
		}

		if ( false !== $args['paragraph_wrap'] ) {
			$message = "<p>$message</p>";
		}

		$markup = sprintf( '<div %1$sclass="%2$s"%3$s>%4$s</div>', $id, $classes, $attributes, $message );

		echo wp_kses_post( $markup );
	}

	/**
	 * Display immediate error.
	 *
	 * @param string $error Message
	 * @param bool   $stop  Stop further dislay, defaults true
	 */
	public function showError( $error, $stop = true ) {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/error.php';

		if ( $stop ) {
			die();
		}
	}

	/**
	 * Add validation error
	 *
	 * @param string $field Field name
	 * @param string $error Message
	 *
	 * @return void
	 */
	protected function addValidationError( $field, $error ) {
		if ( empty( $this->validation_errors[ $field ] ) ) {
			$this->validation_errors[ $field ] = [];
		}

		$this->validation_errors[ $field ][] = $error;
	}

	/**
	 * Retrieve validation error from named field
	 *
	 * @param  string $field Field name, if empty return all errors
	 *
	 * @return array
	 */
	protected function getError( $field = null ) {
		if ( empty( $field ) ) {
			return $this->validation_errors;
		}

		return empty( $this->validation_errors[ $field ] ) ? null : $this->validation_errors[ $field ];
	}
}
