<?php
/**
 * The admin-dashboard of the plugin.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/admin
 */

use \PersonalizeWP\Rule_Conditions\Users_Role;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Dashboard
 */
class PersonalizeWP_Admin_Dashboard_Page extends PersonalizeWP_Admin_Base_Page {

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();
		$this->page_title = __( 'Dashboard', 'personalizewp' );
	}

	/**
	 * Route to the correct action within the page
	 */
	public function route() {
		Users_Role::setComparisonValues();

		$this->indexAction();
	}

	/**
	 * Index action
	 */
	public function indexAction() {
		// Display once onboarding wizard complete
		$has_completed_onboarding = isset( $_GET['onboarding_complete'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used as a flag; data not used.
		if ( $has_completed_onboarding ) {
			$this->add_admin_notice(
				sprintf(
					'<span class="dashicons dashicons-yes-alt"></span><p><strong>%1$s</strong></p><p>%2$s</p>',
					esc_html__( 'You’re all done!', 'personalizewp' ),
					wp_kses_post(
						sprintf(
							/* translators: 1: %s expands to a website link to https://personalizewp.com/knowledge-base/, 2: </a> closing tag, 3: %s expands to a website link to https://personalizewp.com/contact/, 4: </a> closing tag. */
							__( 'Thanks, you’re now able to use the features below. If you get stuck or need help, please read our %1$shelp documentation%2$s, or if you are looking for an enterprise implementation talk to our %3$sperformance team%4$s.', 'personalizewp' ),
							'<a href="' . esc_url( 'https://personalizewp.com/knowledge-base/' ) . '" target="_blank">',
							'</a>',
							'<a href="' . esc_url( 'https://personalizewp.com/contact/' ) . '" target="_blank">',
							'</a>'
						)
					)
				),
				array(
					'type'           => 'success',
					'dismissible'    => true,
					'paragraph_wrap' => false,
				)
			);
		}

		/**
		 * Filters the tiles that are shown on the dashboard page.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tiles {
		 *     Indexed array of tile information to show
		 *
		 *     @type string $status  Banner status to overlay on tile image, can be 'coming' or 'upgrade'
		 *     @type string $img     URL of image to show
		 *     @type string $title   Title of tile
		 *     @type string $content Tile paragraphs
		 *     @type string $url     URL for the whole tile
		 * }
		 */
		$tiles = apply_filters(
			'personalizewp_dashboard_tiles',
			array(
				// Main Rules should always be first
				'personalisation' => array(
					'img'     => plugins_url( '/img/personalizewp_tile_personalization.svg', __FILE__ ),
					'title'   => __( 'Personalization', 'personalizewp' ),
					'content' => __( 'Show or hide blocks based on conditions such as day, time, location, referrer and more', 'personalizewp' ),
					'url'     => PERSONALIZEWP_ADMIN_RULES_INDEX_URL,
				),
				'settings'        => array(
					'img'     => plugins_url( '/img/personalizewp_tile_settings.svg', __FILE__ ),
					'title'   => __( 'Settings', 'personalizewp' ),
					'content' => __( 'Add a Pro license key, edit your settings and join our mailing list', 'personalizewp' ),
					'url'     => PERSONALIZEWP_ADMIN_SETTINGS_INDEX_URL,
				),
			)
		);

		/**
		 * Filters the Pro tiles that are shown on the dashboard page.
		 *
		 * @since 2.6.0
		 *
		 * @param array $tiles {
		 *     Indexed array of tile information to show
		 *
		 *     @type string $status  Banner status to overlay on tile image, can be 'coming' or 'upgrade'
		 *     @type string $img     URL of image to show
		 *     @type string $title   Title of tile
		 *     @type string $content Tile paragraphs
		 *     @type string $url     URL for the whole tile
		 * }
		 */
		$tiles_pro = apply_filters(
			'personalizewp_dashboard_pro_tiles',
			array(
				// Only showing pro version tiles
				'contacts'    => array(
					'status'  => 'upgrade',
					'img'     => plugins_url( '/img/personalizewp_tile_contacts.svg', __FILE__ ),
					'title'   => __( 'Visitor Profiles', 'personalizewp' ),
					'content' => __( 'View the profiles of your site visitors and see their behavior and actions', 'personalizewp' ),
					'url'     => 'https://personalizewp.com/pricing/?ref=profiles',
				),
				'segments'    => array(
					'status'  => 'upgrade',
					'img'     => plugins_url( '/img/personalizewp_tile_segments.svg', __FILE__ ),
					'title'   => __( 'Segments', 'personalizewp' ),
					'content' => __( 'Create groups of visitors based on their behaviour to personalize their experience', 'personalizewp' ),
					'url'     => 'https://personalizewp.com/pricing/?ref=segments',
				),
				'leadscoring' => array(
					'status'  => 'upgrade',
					'img'     => plugins_url( '/img/personalizewp_tile_lead_scoring.svg', __FILE__ ),
					'title'   => __( 'Lead Scoring', 'personalizewp' ),
					'content' => __( 'Apply a weighting score to potential leads based on their website activity', 'personalizewp' ),
					'url'     => 'https://personalizewp.com/pricing/?ref=leadscoring',
				),
			)
		);

		$this->display_header();
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/dashboard.php';
		$this->display_footer();
	}
}
