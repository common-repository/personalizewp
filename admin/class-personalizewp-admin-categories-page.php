<?php
/**
 * Admin screen for managing Rule Categories.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Categories Admin
 */
class PersonalizeWP_Admin_Categories_Page extends PersonalizeWP_Admin_Base_Page {

	/**
	 * Slug for Categories admin page
	 *
	 * @since 1.2.0
	 */
	const SLUG = 'personalizewp/categories';

	/**
	 * Constructor
	 */
	public function __construct() {
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
			'label'   => __( 'Number of Categories per page:', 'personalizewp' ),
			'default' => 20,
			'option'  => 'edit_pwp_categories_per_page',
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
		if ( 'edit_pwp_categories_per_page' === $option ) {
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

		$this->current_action = isset( $_REQUEST['personalizewp_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['personalizewp_action'] ) ) : false;
		$this->form_post      = filter_input( INPUT_POST, 'personalizewp_form', \FILTER_CALLBACK, [ 'options' => 'sanitize_text_field' ] );

		if ( ! empty( $this->form_post ) ) {
			switch ( $this->current_action ) {
				case 'create':
					check_admin_referer( 'category-create' ); // Immediately dies if failing.
					$this->storeAction( $this->form_post );
					break;

				case 'edit':
					if ( ! empty( $this->form_post['id'] ) ) {
						$cat_id = (int) $this->form_post['id'];
						check_admin_referer( 'category-update-' . $cat_id );
						$this->updateAction( $cat_id, $this->form_post );
					}
					break;
			}
		}

		switch ( $this->current_action ) {
			case 'delete':
				if ( ! empty( $_GET['id'] ) ) {
					$cat_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					check_admin_referer( 'category-delete-' . $cat_id );
					$this->deleteAction( $cat_id );
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
					// Processing create has failed, so re-load the form.
					check_admin_referer( 'category-create' );
					$this->createAction( $this->form_post );
				} else {
					$this->createAction();
				}
				return;

			case 'edit':
				if ( ! empty( $_GET['id'] ) ) {
					$cat_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					$this->editAction( $cat_id );
					return;
				}
				break;

			case 'rules':
				if ( ! empty( $_GET['id'] ) ) {
					$cat_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					$this->viewRulesAction( $cat_id );
					return;
				}
				break;
		}

		$this->indexAction();
	}

	/**
	 * Index action
	 */
	public function indexAction() {
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
		// Trigger messaging from actions.
		if ( isset( $_REQUEST['created'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Category created', 'personalizewp' );
		}
		if ( isset( $_REQUEST['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Category updated', 'personalizewp' );
		}
		if ( isset( $_REQUEST['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is only displaying a message with no dynamic values.
			$status_message = __( 'Category deleted', 'personalizewp' );
		}
		if ( $status_message ) {
			$this->add_admin_notice(
				'<span class="dashicons dashicons-yes-alt"></span>' . $status_message,
				array(
					'type'        => 'success',
					'id'          => 'message',
					'dismissible' => true,
				)
			);
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/categories/index.php';
		$this->display_footer();
	}

	/**
	 * Create action - Create a placeholder category object display form
	 *
	 * @param array $data Form data
	 */
	protected function createAction( $data = [] ) {
		$category = new PersonalizeWP_Category();

		if ( $data ) {
			$category->populateFromArray( $data );
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/categories/create.php';
		$this->display_footer();
	}

	/**
	 * Store action - process data from form submitted on "create" page
	 * Redirects after validating and storing.
	 *
	 * @param array $data Form data
	 */
	protected function storeAction( $data ) {
		$this->validate( $data );

		if ( empty( $this->getError() ) ) {
			$this->saveCategory( $data );

			wp_safe_redirect( add_query_arg( array( 'created' => 1 ), PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL ) );
			exit;
		}
	}

	/**
	 * Edit action - display form for existing category
	 *
	 * @param integer $id Category ID
	 */
	protected function editAction( $id ) {
		$category = PersonalizeWP_Category::find( $id );

		if ( ! $category ) {
			$this->showError( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'personalizewp' ) );
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/categories/edit.php';
		$this->display_footer();
	}

	/**
	 * Edit action - display form for existing category
	 *
	 * @param integer $id Category ID
	 */
	protected function viewRulesAction( $id ) {
		// This is used within the partial template below.
		$category = PersonalizeWP_Category::find( $id );

		if ( ! $category ) {
			$this->showError( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'personalizewp' ) );
		}

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/categories/rules.php';
		$this->display_footer();
	}

	/**
	 * Update action - process data from form submitted on "edit" page
	 * Redirects after validating and storing.
	 *
	 * @param integer $id   Category ID
	 * @param array   $data Form data
	 */
	protected function updateAction( $id, $data ) {
		$category = PersonalizeWP_Category::find( $id );

		if ( ! $category ) {
			$this->showError( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'personalizewp' ) );
		}

		if ( ! $category->can_edit ) {
			$this->showError( __( 'This category cannot be edited', 'personalizewp' ) );
		}

		$this->validate( $data, true );

		if ( empty( $this->getError() ) ) {
			$this->saveCategory( $data, $id );

			wp_safe_redirect( add_query_arg( array( 'updated' => 1 ), PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL ) );
			exit;
		}
	}

	/**
	 * Delete action
	 *
	 * @param integer $id Category ID
	 */
	protected function deleteAction( $id ) {
		$category = PersonalizeWP_Category::find( $id );

		if ( ! $category ) {
			// Nothing to do
			wp_safe_redirect( PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL );
			exit;
		}

		if ( ! $category->can_delete ) {
			$this->showError( __( 'Category cannot be deleted', 'personalizewp' ) );
		}

		PersonalizeWP_Category::delete( $id );

		wp_safe_redirect( add_query_arg( array( 'deleted' => 1 ), PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL ) );
		exit;
	}

	/**
	 * Save category to DB (either create or update)
	 *
	 * @param  array   $data Form data
	 * @param  integer $id   Category ID
	 * @return PersonalizeWP_Category
	 */
	protected function saveCategory( $data, $id = null ) {
		if ( $id ) {
			$category = PersonalizeWP_Category::find( $id );
		}

		if ( empty( $category ) ) {
			$category = new PersonalizeWP_Category();
		}

		$category->populateFromArray(
			[
				'name' => $data['name'],
			]
		);

		$category->save();

		return $category;
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
				$this->addValidationError( 'form', __( 'ID is required to update a category', 'personalizewp' ) );
			}
		}

		if ( empty( $data['name'] ) ) {
			$this->addValidationError( 'name', __( 'Name is required', 'personalizewp' ) );
		}

		if ( PersonalizeWP_Category::check_name( $data['name'], $data['id'] ) ) {
			$this->addValidationError( 'name', __( 'This category name already exists. Please choose a different name.', 'personalizewp' ) );
		}
	}
}
