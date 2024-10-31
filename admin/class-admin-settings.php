<?php
/**
 * The admin-settings of the plugin.
 *
 * @link       https://personalizewp.com
 * @since      2.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

use PersonalizeWP_Admin_Base_Page;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Settings
 */
class Admin_Settings extends PersonalizeWP_Admin_Base_Page {

	/**
	 * Holds instance of plugin object
	 *
	 * @var PersonalizeWP
	 */
	private $plugin;

	/**
	 * Class constructor.
	 */
	protected function __construct() {
		parent::__construct();

		$this->plugin = \personalizewp();

		add_action( 'wp_ajax_pwp_newsletter_signup', [ $this, 'process_newsletter_signup' ] );

		$this->page_title = __( 'Settings', 'personalizewp' );
	}

	/**
	 * Settings screen
	 */
	public function display_screen() {

		$option_key = $this->plugin->settings->option_key;
		$sections   = $this->plugin->settings->get_fields();
		$active_tab = filter_input( INPUT_GET, 'tab', \FILTER_CALLBACK, [ 'options' => 'sanitize_text_field' ] );
		?>

		<?php $this->display_header(); ?>

		<?php $this->display_onboarded_message(); ?>

		<?php settings_errors(); ?>

		<main class="with-sidebar"  data-direction="rtl">
			<section class="section">

				<?php
				// No sections, no need for tabs
				if ( count( $sections ) > 1 ) :
					?>
					<ul data-tabs class="settings-tabs">
						<?php
						$i = 0;
						foreach ( $sections as $section => $data ) :
							++$i;
							$is_active = ( ( 1 === $i && ! $active_tab ) || $active_tab === $section );
							?>
							<li>
								<a href="<?php echo esc_attr( '#pwp-' . $section ); ?>"
									<?php echo $is_active ? 'data-tabby-default' : ''; ?>
									class="nav-tab <?php echo $is_active ? esc_attr( 'is-active' ) : ''; ?>">
									<?php echo esc_html( $data['title'] ); ?>
								</a>
							</li>
							<?php
						endforeach;
						?>
					</ul>
					<?php
				endif;
				?>

				<div class="nav-tab-content">
					<?php
					// Check for fields, no fields, no need to show form
					$has_fields = false;
					foreach ( $sections as $section_name => $data ) :
						if ( ! empty( $data['fields'] ) ) :
							$has_fields = true;
							break;
						endif;
					endforeach;
					?>

					<div data-tabby-default id="pwp-general" class="tab-panel">
						<?php
						$this->display_general_tab();
						// General fields are outside of the settings form, but still apply to the form below.
						if ( ! empty( $sections['general']['fields'] ) ) :
							// Manually trigger section fields.
							$this->do_settings_section( 'general' );
						endif;
						?>
					</div>

					<?php
					if ( $has_fields ) :
						// Form wraps all other tabs
						?>
						<form id="pwp_form_settings" method="post" action="<?php echo esc_attr( admin_url( 'options.php' ) ); ?>" enctype="multipart/form-data">
							<?php
							settings_fields( $option_key );
							// Each setting section will wrap itself as a separate tab.
							foreach ( $sections as $section_name => $data ) :
								// General already handled above.
								if ( 'general' === $section_name ) :
									continue;
								endif;

								$this->do_settings_section( $section_name );
							endforeach;

							submit_button( __( 'Save Settings', 'personalizewp' ) );
							?>
						</form>
						<?php
					endif;
					?>
				</div>

			</section>
			<?php $this->display_sidebar(); ?>
		</main>

		<?php
		$this->display_footer();
	}

	/**
	 * Display the onboarded notice after onboarding is complete
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function display_onboarded_message() {

		$is_onboarding = ( '1' !== get_option( 'pwp_admin_onboarding_dismissed', '' ) );
		$has_signed_up = (bool) get_user_meta( get_current_user_id(), 'pwp_newsletter_signup', false );

		// Only show when still using for first time (legacy onboarding), but not after form completed.
		// Dismissing this updates the option globally, dismissing all the 'first time' messaging.
		if ( $is_onboarding && ! $has_signed_up ) {
			// The option key ensures persistancy.
			$this->add_admin_notice(
				sprintf(
					'<span class="dashicons dashicons-info"></span><p><strong>%1$s</strong></p><p>%2$s</p>',
					esc_html__( 'First time configuration', 'personalizewp' ),
					esc_html__( 'Thanks for installing and activating PersonalizeWP. We recommend that if it is your first time with the plugin, that you sign up for our email list, where we plan to share tips and tricks of things you can do with PersonalizeWP, as well as inform you when new versions and features are added to the plugin.', 'personalizewp' ),
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
	}

	/**
	 * Display the general tab of the settings
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function display_general_tab() {

		/**
		 * Fires at the top of the container for the settings page on the general tab.
		 */
		do_action( 'personalizewp_before_settings' );

		$has_signed_up = (bool) get_user_meta( get_current_user_id(), 'pwp_newsletter_signup', false );
		if ( $has_signed_up ) {
			?>
			<div id="newsletter-signup-success" class="pwp-panel">
				<h2><?php esc_html_e( 'You’re on the list!', 'personalizewp' ); ?></h2>
				<p><?php esc_html_e( 'Now you’re on our email list, we’ll share tips and tricks of things you can do with PersonalizeWP, as well as inform you when new versions and features are added to the plugin. If you’d like to opt-out of receiving email, then click on the Manage Preference link at the bottom of any emails we send you.', 'personalizewp' ); ?></p>
			</div>
			<?php
			return;
		}

		include plugin_dir_path( __FILE__ ) . 'partials/_other/newsletter-signup.php';
	}

	/**
	 * Prints out individual settings section added to a particular settings section.
	 *
	 * Based on the WordPress default function `do_settings_sections`.
	 *
	 * @global array $wp_settings_sections Storage array of all settings sections added to admin pages.
	 * @global array $wp_settings_fields Storage array of settings fields and info about their pages/sections.
	 * @since 2.2.0
	 *
	 * @param string $section_name The slug name of the settings section to output.
	 */
	private function do_settings_section( $section_name ) {
		global $wp_settings_sections, $wp_settings_fields;

		$page = $this->plugin->settings->option_key;

		if ( ! isset( $wp_settings_sections[ $page ] ) && ! isset( $wp_settings_sections[ $page ][ $section_name ] ) ) {
			return;
		}

		$section = (array) $wp_settings_sections[ $page ][ $section_name ];

		if ( '' !== $section['before_section'] ) {
			if ( '' !== $section['section_class'] ) {
				echo wp_kses_post( sprintf( $section['before_section'], esc_attr( $section['section_class'] ) ) );
			} else {
				echo wp_kses_post( $section['before_section'] );
			}
		}

		if ( $section['title'] ) {
			echo wp_kses_post( "<h2>{$section['title']}</h2>\n" );
		}

		if ( $section['callback'] ) {
			call_user_func( $section['callback'], $section );
		}

		if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
			return;
		}
		echo '<table class="form-table" role="presentation">';
		do_settings_fields( $page, $section['id'] );
		echo '</table>';

		if ( '' !== $section['after_section'] ) {
			echo wp_kses_post( $section['after_section'] );
		}
	}

	/**
	 * Display the settings page sidebar of tiles
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function display_sidebar() {

		/**
		 * Filters the tiles that are shown on the settings page.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tiles {
		 *     Indexed array of tile information to show
		 *
		 *     @type string $img     URL of image to show
		 *     @type string $title   Title of tile
		 *     @type string $content Tile paragraphs
		 *     @type string $button  Button text
		 *     @type string $url     URL for the button
		 * }
		 */
		$tiles = apply_filters(
			'personalizewp_settings_tiles',
			array(
				'blackfriday'   => array(
					'img'     => plugins_url( '/img/personalizewp_tile_blackfriday.jpg', __FILE__ ),
					'title'   => __( 'Boost Your Black Friday Sales', 'personalizewp' ),
					'content' => __( 'Tailor your offers for maximum impact this Black Friday – personalized experiences that convert!', 'personalizewp' ),
					'button'  => __( 'Find Out More', 'personalizewp' ),
					'url'     => 'https://personalizewp.com/get-ready-for-black-friday/',
				),
				'knowledgebase' => array(
					'img'     => plugins_url( '/img/personalizewp_tile_knowledge_base.svg', __FILE__ ),
					'title'   => __( 'Need more information?', 'personalizewp' ),
					'content' => __( 'We have developed a comprehensive knowledge base to help support you', 'personalizewp' ),
					'button'  => __( 'Visit Knowledge Base', 'personalizewp' ),
					'url'     => 'https://personalizewp.com/knowledge-base/',
				),
			),
		);

		if ( empty( $tiles ) ) {
			return;
		}

		?>
		<aside class="pwp-tiles grid cta-sidebar">
		<?php
		foreach ( $tiles as $tile ) :
			include plugin_dir_path( __FILE__ ) . 'partials/_other/tile.php';
		endforeach;
		?>
		</aside>
		<?php
	}

	/**
	 * Sign up for newsletter via AJAX callback
	 *
	 * @return void
	 */
	public function process_newsletter_signup() {

		$json = [
			'errors' => [],
		];

		check_ajax_referer( 'newsletter-signup' );

		$first_name       = ! empty( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : null;
		$last_name        = ! empty( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : null;
		$email_address    = ! empty( $_POST['email_address'] ) ? sanitize_email( wp_unslash( $_POST['email_address'] ) ) : null;
		$terms_acceptance = ! empty( $_POST['terms_acceptance'] ) ? (bool) wp_unslash( $_POST['terms_acceptance'] ) : false;
		// Can't use `wp_hash` as that uses a local salt. This has to be saltless
		$verify = hash_hmac(
			'md5',
			wp_nonce_tick( 'PWP-sub' ) . '|PWP-sub|' . implode(
				'|',
				array(
					$first_name,
					$last_name,
					$email_address,
				)
			),
			''
		);

		if ( empty( $first_name ) ) {
			$json['errors'][] = array(
				'input'   => 'first_name',
				'message' => esc_html__( 'Please provide your first name', 'personalizewp' ),
			);
		}

		if ( empty( $last_name ) ) {
			$json['errors'][] = array(
				'input'   => 'last_name',
				'message' => esc_html__( 'Please provide your last name', 'personalizewp' ),
			);
		}

		if ( empty( $email_address ) || ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
			$json['errors'][] = array(
				'input'   => 'email_address',
				'message' => esc_html__( 'Please provide your email address', 'personalizewp' ),
			);
		}

		if ( ! $terms_acceptance ) {
			$json['errors'][] = array(
				'input'   => 'terms_acceptance',
				'message' => esc_html__( 'Please accept the Terms and Conditions', 'personalizewp' ),
			);
		}

		if ( ! empty( $json['errors'] ) ) {
			wp_send_json_error( $json );
			exit;
		}

		// Send to our server to signup to Newsletter.
		$api_response = wp_remote_post(
			esc_url( 'https://personalizewp.com/wp-json/personalizewp/v1/submission' ),
			array(
				'body' => array(
					'first_name'       => $first_name,
					'last_name'        => $last_name,
					'email_address'    => $email_address,
					'terms_acceptance' => 1,
					'verify'           => $verify,
				),
			)
		);

		if ( ! is_wp_error( $api_response ) && ! empty( $api_response['response']['code'] ) && 200 === $api_response['response']['code'] ) {
			// Mark that the user has signed up, so they no longer see the newsletter signup form.
			add_user_meta( get_current_user_id(), 'pwp_newsletter_signup', true );

			unset( $json['errors'] );
			// Display pre-set confirmation message.
			$json['confirmation'] = 1;
			wp_send_json_success( $json );
			exit;
		}

		$json['errors'][] = array(
			'input'   => false,
			'message' => esc_html__( 'Something went wrong, please try again', 'personalizewp' ),
		);

		wp_send_json_error( $json );
		exit;
	}
}
