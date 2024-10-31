<?php
/**
 * Admin screen for managing Rule.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

use \PersonalizeWP\Rules_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Rules Admin
 */
class PersonalizeWP_Admin_Rules_Page extends PersonalizeWP_Admin_Base_Page {

	/**
	 * Slug for Rules admin page
	 *
	 * @since 1.2.0
	 */
	const SLUG = 'personalizewp/rules';

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();

		$this->page_title = __( 'Personalization', 'personalizewp' );

		add_filter( 'set-screen-option', [ $this, 'set_screen_options' ], 10, 3 );
	}

	/**
	 * Display the Dashboard screen options.
	 *
	 * @since 1.2.0
	 */
	public function dashboard_screen_options() {
		$screen = get_current_screen();

		$is_screen = is_object( $screen ) && 'personalize_page_' . self::SLUG === $screen->id;
		if ( ! $is_screen ) {
			return;
		}
		$args = array(
			'label'   => __( 'Number of Rules per page:', 'personalizewp' ),
			'default' => 20,
			'option'  => 'edit_pwp_rules_per_page',
		);
		add_screen_option( 'per_page', $args );
	}

	/**
	 * Store the screen options.
	 *
	 * @since 1.2.0
	 *
	 * @param mixed  $screen_option The value to save instead of the option value.
	 *                              Default false (to skip saving the current option).
	 * @param string $option        The option name.
	 * @param int    $value         The option value.
	 *
	 * @return string
	 */
	public function set_screen_options( $screen_option, $option, $value ) {
		if ( 'edit_pwp_rules_per_page' === $option ) {
			return $value;
		}
		return $screen_option;
	}

	/**
	 * Process - typically used for processing POST data
	 *
	 * @return void
	 */
	public function process() {
		// Double check for user caps before any possible processing of data.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->current_action = isset( $_REQUEST['personalizewp_action'] ) ? sanitize_text_field( $_REQUEST['personalizewp_action'] ) : false;
		$this->form_post      = filter_input( INPUT_POST, 'personalizewp_form', \FILTER_CALLBACK, [ 'options' => 'sanitize_text_field' ] );

		if ( ! empty( $this->form_post ) ) {
			switch ( $this->current_action ) {
				case 'create':
					check_admin_referer( 'rule-create' ); // Immediately dies if failing.
					$this->storeAction( $this->form_post );
					break;

				case 'edit':
					if ( ! empty( $this->form_post['id'] ) ) {
						$rule_id = (int) $this->form_post['id'];
						check_admin_referer( 'rule-update-' . $rule_id );
						$this->updateAction( $rule_id, $this->form_post );
					}
					break;
			}
		}

		switch ( $this->current_action ) {
			case 'delete':
				if ( ! empty( $_GET['id'] ) ) {
					$rule_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					check_admin_referer( 'rule-delete-' . $rule_id );
					$this->deleteAction( $rule_id );
				}
				break;

			case 'duplicate':
				if ( ! empty( $_GET['id'] ) ) {
					$rule_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					check_admin_referer( 'rule-duplicate-' . $rule_id );
					$this->duplicateAction( $rule_id );
				}
				break;
		}
	}

	/**
	 * Route to the correct action within the page
	 *
	 * @return void
	 */
	public function route() {
		switch ( $this->current_action ) {
			case 'create':
				if ( ! empty( $_REQUEST['_wpnonce'] ) ) {
					check_admin_referer( 'rule-create' );
					$this->createAction( $this->form_post );
					return;
				}
				break;

			case 'edit':
				if ( ! empty( $_GET['id'] ) ) {
					$edit_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					$this->editAction( $edit_id );
					return;
				}
				break;
		}

		$this->indexAction();
	}

	/**
	 * Index action
	 */
	protected function indexAction() {
		$is_onboarding = ( '1' !== get_option( 'pwp_admin_onboarding_dismissed', '' ) );
		// Dismissing this updates the option globally, dismissing all the 'first time' messaging.
		if ( $is_onboarding ) {
			// The option key ensures persistancy.
			$this->add_admin_notice(
				sprintf(
					'<span class="dashicons dashicons-info"></span><p><strong>%1$s</strong></p><p>%2$s</p>',
					esc_html__( 'First time configuration', 'personalizewp' ),
					esc_html__( 'PersonalizeWP provides a number of pre-built rules and categories that are ready to use on your site, which you can see below. If you want to add your own, just click on the Create Rule or Create Category button.', 'personalizewp' ),
				),
				array(
					'type'               => 'info',
					'dismissible'        => true,
					'paragraph_wrap'     => false,
					'additional_classes' => array( 'onboarding' ),
					'attributes'         => array( 'data-dismiss-type' => 'onboarding' ),
				)
			);
		}

		$status_message = '';
		$status_type    = 'success';
		$icon_type      = 'yes-alt';
		// Trigger messaging from actions.
		if ( isset( $_REQUEST['created'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Rule created', 'personalizewp' );
		}
		if ( isset( $_REQUEST['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Rule deleted', 'personalizewp' );
		}
		if ( isset( $_REQUEST['duplicate_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Error duplicating rule', 'personalizewp' );
			$status_type    = 'error';
			$icon_type      = 'error';
		}
		if ( $status_message ) {
			$this->add_admin_notice(
				sprintf(
					'<span class="dashicons dashicons-%1$s"></span>%2$s',
					$icon_type,
					$status_message
				),
				array(
					'type'        => $status_type,
					'id'          => 'message',
					'dismissible' => true,
				)
			);
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/rules/index.php';
		$this->display_footer();
	}

	/**
	 * Create action - display form
	 *
	 * @param array $data Form data
	 */
	protected function createAction( $data ) {
		$rule = new PersonalizeWP_Rule();

		if ( $data ) {
			$rule->populateFromArray( $data );
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/rules/create.php';
		$this->display_footer();
	}

	/**
	 * Store action - process data from form submitted on "create" page
	 *
	 * @param array $data Form data
	 */
	protected function storeAction( $data ) {
		$this->validate( $data );

		if ( empty( $this->getError() ) ) {
			$this->saveRule( $data );

			wp_safe_redirect( add_query_arg( array( 'created' => 1 ), PERSONALIZEWP_ADMIN_RULES_INDEX_URL ) );
			exit;
		}
	}

	/**
	 * Edit action - display form for existing rule
	 *
	 * @param integer $id Rule ID
	 */
	protected function editAction( $id ) {
		// This is used within the partial template below.
		$rule = PersonalizeWP_Rule::find( $id );

		if ( ! $rule ) {
			$this->showError( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'personalizewp' ) );
		}

		$status_message = '';
		$status_type    = 'success';
		$icon_type      = 'yes-alt';
		// Trigger messaging from actions.
		if ( isset( $_REQUEST['duplicated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Rule duplicated', 'personalizewp' );
		}
		if ( $status_message ) {
			$this->add_admin_notice(
				sprintf(
					'<span class="dashicons dashicons-%1$s"></span>%2$s',
					$icon_type,
					$status_message
				),
				array(
					'type'        => $status_type,
					'id'          => 'message',
					'dismissible' => true,
				)
			);
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/rules/edit.php';
		$this->display_footer();
	}

	/**
	 * Update action - process data from form submitted on "edit" page
	 *
	 * @param  integer $id   Rule ID
	 * @param  array   $data Form data
	 */
	protected function updateAction( $id, $data ) {
		$rule = PersonalizeWP_Rule::find( $id );

		if ( ! $rule ) {
			$this->showError( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'personalizewp' ) );
		}

		$this->validate( $data, true );

		if ( empty( $this->getError() ) ) {
			$this->saveRule( $data, $id );

			$this->add_admin_notice(
				'<span class="dashicons dashicons-yes-alt"></span>' . __( 'Rule updated', 'personalizewp' ),
				array(
					'type'        => 'success',
					'id'          => 'message',
					'dismissible' => true,
				)
			);

			// No redirect as re-loading the same edit screen.
		}
	}

	/**
	 * Delete action
	 *
	 * @param  integer $id Rule ID
	 */
	protected function deleteAction( $id ) {
		$rule = PersonalizeWP_Rule::find( $id );

		if ( ! $rule ) {
			// Nothing to do
			wp_safe_redirect( PERSONALIZEWP_ADMIN_RULES_INDEX_URL );
			exit;
		}

		if ( ! $rule->can_delete ) {
			$this->showError( __( 'Rule cannot be deleted', 'personalizewp' ) );
		}

		PersonalizeWP_Rule::delete( $id );

		wp_safe_redirect( add_query_arg( array( 'deleted' => 1 ), PERSONALIZEWP_ADMIN_RULES_INDEX_URL ) );
		exit;
	}

	/**
	 * Duplicate action
	 *
	 * @param  integer $id Rule ID
	 */
	protected function duplicateAction( $id ) {
		$rule = PersonalizeWP_Rule::find( $id );

		if ( ! $rule ) {
			wp_safe_redirect( add_query_arg( array( 'duplicate_error' => 1 ), PERSONALIZEWP_ADMIN_RULES_INDEX_URL ) );
			exit;
		}

		$duplicate_rule = $rule->clone();

		if ( $duplicate_rule instanceof PersonalizeWP_Rule ) {
			wp_safe_redirect( add_query_arg( array( 'duplicated' => 1 ), $duplicate_rule->getEditUrlAttribute() ) );
		} else {
			wp_safe_redirect( add_query_arg( array( 'duplicate_error' => 1 ), PERSONALIZEWP_ADMIN_RULES_INDEX_URL ) );
		}
		exit;
	}

	/**
	 * Save rule to DB (either create or update)
	 *
	 * @param  array   $data Form data
	 * @param  integer $id   Rule ID
	 * @return PersonalizeWP_Rule
	 */
	protected function saveRule( $data, $id = null ) {
		if ( $id ) {
			$rule = PersonalizeWP_Rule::find( $id );
		}

		if ( empty( $rule ) ) {
			$rule = new PersonalizeWP_Rule();
		}

		$rule->populateFromArray( $data );

		// Is new rule
		if ( empty( $rule->id ) ) {
			$rule->type       = PersonalizeWP_Rule_Types::$CUSTOM;
			$rule->created_by = get_current_user_id();
		}

		$rule->save();

		return $rule;
	}

	/**
	 * Validate data, likely from submitted form
	 *
	 * @param  array   $data           Form data
	 * @param  boolean $existing_model True if we're editing an existing model
	 * @return void
	 */
	protected function validate( $data, $existing_model = false ) {
		if ( empty( $data ) ) {
			$this->addValidationError( 'form', __( 'Form is not valid', 'personalizewp' ) );
			return;
		}

		if ( $existing_model ) {
			if ( empty( $data['id'] ) ) {
				$this->addValidationError( 'form', __( 'ID is required to update a rule', 'personalizewp' ) );
			}
		}

		if ( empty( $data['name'] ) ) {
			$this->addValidationError( 'name', __( 'Please enter a name', 'personalizewp' ) );
		}

		if ( PersonalizeWP_Rule::check_name( $data['name'], $data['id'] ) ) {
			$this->addValidationError( 'name', __( 'This rule name already exists. Please choose a different name.', 'personalizewp' ) );
		}

		if ( empty( $data['category_id'] ) ) {
			$this->addValidationError( 'category_id', __( 'Please select a category', 'personalizewp' ) );
		}

		$valid_operators   = [ 'ANY', 'ALL' ];
		$is_valid_operator = ( ! empty( $data['operator'] ) && in_array( $data['operator'], $valid_operators, true ) );
		$data['operator']  = $is_valid_operator ? $data['operator'] : PersonalizeWP_Rule::$default_operator;

		if ( ! empty( $data['conditions']['measure'] ) ) {
			foreach ( $data['conditions']['measure'] as $i => $measure ) {
				if ( empty( $measure ) ) {
					$this->addValidationError( 'conditions[' . $i . ']', __( 'A measure for the condition should be selected', 'personalizewp' ) );
				}
			}
		}

		if ( ! empty( $data['conditions']['meta_value'] ) ) {
			foreach ( $data['conditions']['meta_value'] as $i => $meta_value ) {
				$condition_class = Rules_Conditions::get_class( $data['conditions']['measure'][ $i ] );

				if ( empty( $meta_value ) && $condition_class && ! empty( $condition_class->measure_key ) ) {
					$this->addValidationError( 'conditions[' . $i . ']', __( 'The meta value for the condition should not be empty', 'personalizewp' ) );
				}
			}
		}

		if ( ! empty( $data['conditions']['comparator'] ) ) {
			foreach ( $data['conditions']['comparator'] as $i => $comparator ) {
				if ( empty( $comparator ) ) {
					$this->addValidationError( 'conditions[' . $i . ']', __( 'A comparator for the condition should be selected', 'personalizewp' ) );
				}
			}
		}

		if ( ! empty( $data['conditions']['raw_value'] ) ) {
			foreach ( $data['conditions']['raw_value'] as $i => $value ) {
				$comparitor_value = $data['conditions']['comparator'][ $i ];

				if ( empty( $value ) && 'any_value' !== $comparitor_value && 'no_value' !== $comparitor_value ) {
					if ( $data['conditions'] ) {
						$this->addValidationError( 'conditions[' . $i . ']', __( 'Please select a value', 'personalizewp' ) );
					}
				}
			}
		}
	}
}
