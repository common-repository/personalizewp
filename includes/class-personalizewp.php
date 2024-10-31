<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link    https://personalizewp.com
 * @since   1.0.0
 *
 * @package PersonalizeWP
 */

namespace PersonalizeWP;

use PersonalizeWP\Traits\SingletonTrait;
use PersonalizeWP\Settings;
use PersonalizeWP\Admin\Admin;
use PersonalizeWP\Admin\Admin_Blocks;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    PersonalizeWP
 */
final class PersonalizeWP {

	use SingletonTrait;

	/**
	 * Setup flag to ensure only run once
	 *
	 * @since 2.5.0
	 *
	 * @var boolean
	 */
	private $is_setup = false;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $personalizewp    The string used to uniquely identify this plugin.
	 */
	protected $personalizewp;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * URLs and Paths used by the plugin
	 *
	 * @since 1.2.0
	 *
	 * @var array
	 */
	public $locations = array();

	/**
	 * GEOIP Reader
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private $geo_reader;

	/**
	 * Stores and manages WordPress settings.
	 *
	 * @since 2.0.0
	 * @var Settings
	 */
	public $settings;

	/**
	 * Initialises the instance
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Added $file param
	 * @since 2.5.0 Removed param, use singletontrait
	 */
	private function __construct() {

		if ( defined( 'PERSONALIZEWP_VERSION' ) ) {
			$this->version = PERSONALIZEWP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->personalizewp = 'personalizewp';
	}

	/**
	 * Setup the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Main plugin file
	 */
	public function setup( $file ) {

		// Don't double setup
		if ( $this->is_setup ) {
			return;
		}
		$this->is_setup = true;

		$dir_path = plugin_dir_path( $file );
		$dir_url  = plugin_dir_url( $file );

		$this->locations = array(
			'file'    => $file,
			'dir'     => $dir_path,
			'url'     => $dir_url,
			'inc_dir' => $dir_path . 'includes/',
		);

		$this->load_dependencies();
		$this->setup_admin();
		$this->setup_public();

		add_action( 'plugins_loaded', [ $this, 'loaded' ] );
		add_action( 'init', [ $this, 'i18n' ] );
	}

	/**
	 * Whether a premium add-on (i.e. Pro or Standard) also exists in the sites' plugin directory.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public static function has_premium_installed() {

		$all_plugins  = array_keys( get_plugins() );
		$has_standard = in_array( 'personalizewp-standard/personalizewp-pro.php', $all_plugins, true );
		$has_pro      = in_array( 'personalizewp-pro/personalizewp-pro.php', $all_plugins, true );

		return $has_standard || $has_pro;
	}

	/**
	 * Whether this is a Pro version of a plugin.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_pro() {

		return apply_filters( 'personalizewp_is_pro', false );
	}

	/**
	 * Runs after PersonalizeWP is loaded.
	 *
	 * Initializes add-ons and integrations with other plugins.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function loaded() {

		/**
		 * Fires when PersonalizeWP has loaded.
		 *
		 * When developing Add-Ons, use this hook to initialize any functionality that depends on PersonalizeWP functionality.
		 *
		 * @since 2.0.0 Added the PersonalizeWP param
		 *
		 * @param PersonalizeWP $this The main PersonalizeWP object
		 */
		do_action( 'personalizewp_loaded', $this );

		$this->load_integrations();
	}

	/**
	 * Initializes integrations with other plugins.
	 *
	 * @since  2.5.0
	 * @access private
	 *
	 * @return void
	 */
	private function load_integrations() {

		$integrations = glob( $this->plugin_path( 'includes/integrations/*.php' ) );
		foreach ( array_filter( $integrations, 'is_file' ) as $file_path ) {
			include_once $file_path;
		}

		/**
		 * Fires when PersonalizeWP is loading integrations
		 *
		 * When developing Add-Ons, use this hook to initialize any integration functionality.
		 *
		 * @since 2.5.0
		 *
		 * @param PersonalizeWP $this The main PersonalizeWP object
		 */
		do_action( 'personalizewp_register_integrations', $this );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		require_once $this->plugin_path( 'vendor/autoload.php' );

		$this->settings = Settings::instance();

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once $this->plugin_path( 'admin/class-personalizewp-admin-base-page.php' );
		require_once $this->plugin_path( 'admin/class-personalizewp-admin-categories-page.php' );
		require_once $this->plugin_path( 'admin/class-personalizewp-admin-dashboard-page.php' );
		require_once $this->plugin_path( 'admin/class-personalizewp-admin-rules-page.php' );

		require_once $this->plugin_path( 'includes/personalizewp-constants.php' );

		require_once $this->plugin_path( 'includes/models/base.php' );
		require_once $this->plugin_path( 'includes/models/category.php' );
		require_once $this->plugin_path( 'includes/models/rule.php' );
		require_once $this->plugin_path( 'includes/models/block.php' );

		require_once $this->plugin_path( 'includes/class-personalizewp-rule-types.php' );
	}

	/**
	 * Loads the plugin's text domain for internationalization.
	 *
	 * @since 2.5.0
	 * @access public
	 */
	public function i18n() {

		load_plugin_textdomain(
			'personalizewp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Register/initialise all of the classes and hooks related to the
	 * admin area functionality of the plugin.
	 *
	 * @since  2.5.0
	 * @access private
	 */
	private function setup_admin() {

		Admin::instance();
		Admin_Blocks::instance();
	}

	/**
	 * Register/initialise all of the classes and hooks related to the
	 * public-facing functionality of the plugin.
	 *
	 * @since  2.5.0
	 * @access private
	 */
	private function setup_public() {

		Block_Renderer::instance();
		PublicFacing::instance();
	}

	/**
	 * Convert an IP Address into a two letter country code following ISO 3166-1.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ip_address IPv4 or IPv6 address as a string
	 *
	 * @return string ISOCode on success, empty string otherwise
	 */
	public function convert_ip_to_isocode( $ip_address = '' ) {

		if ( ! isset( $this->geo_reader ) ) {
			$this->geo_reader = new \GeoIp2\Database\Reader( $this->plugin_path( 'GeoLite2-Country.mmdb' ) );
		}
		if ( empty( $ip_address ) ) {
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : false;
		}
		if ( empty( $ip_address ) ) {
			return '';
		}

		try {
			$record = $this->geo_reader->country( $ip_address );
		} catch ( \Exception $e ) {
			return '';
		}

		return $record->country->isoCode;
	}

	/**
	 * Return all plugin options, or optionally just a single option.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option Individual option to return, or empty to return all
	 *
	 * @return mixed
	 */
	public function get_setting( $option = '' ) {

		$options = $this->settings->get_options();
		if ( empty( $option ) ) {
			return $options;
		}

		return isset( $options[ $option ] ) ? $options[ $option ] : null;
	}

	/**
	 * Return the PersonalizeWP supported Countries
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_countries() {

		return [
			'AF' => _x( 'Afghanistan', 'countries', 'personalizewp' ),
			'AX' => _x( 'Åland Islands', 'countries', 'personalizewp' ),
			'AL' => _x( 'Albania', 'countries', 'personalizewp' ),
			'DZ' => _x( 'Algeria', 'countries', 'personalizewp' ),
			'AS' => _x( 'American Samoa', 'countries', 'personalizewp' ),
			'AD' => _x( 'Andorra', 'countries', 'personalizewp' ),
			'AO' => _x( 'Angola', 'countries', 'personalizewp' ),
			'AI' => _x( 'Anguilla', 'countries', 'personalizewp' ),
			'AQ' => _x( 'Antarctica', 'countries', 'personalizewp' ),
			'AG' => _x( 'Antigua and Barbuda', 'countries', 'personalizewp' ),
			'AR' => _x( 'Argentina', 'countries', 'personalizewp' ),
			'AM' => _x( 'Armenia', 'countries', 'personalizewp' ),
			'AW' => _x( 'Aruba', 'countries', 'personalizewp' ),
			'AU' => _x( 'Australia', 'countries', 'personalizewp' ),
			'AT' => _x( 'Austria', 'countries', 'personalizewp' ),
			'AZ' => _x( 'Azerbaijan', 'countries', 'personalizewp' ),
			'BS' => _x( 'Bahamas', 'countries', 'personalizewp' ),
			'BH' => _x( 'Bahrain', 'countries', 'personalizewp' ),
			'BD' => _x( 'Bangladesh', 'countries', 'personalizewp' ),
			'BB' => _x( 'Barbados', 'countries', 'personalizewp' ),
			'BY' => _x( 'Belarus', 'countries', 'personalizewp' ),
			'BE' => _x( 'Belgium', 'countries', 'personalizewp' ),
			'BZ' => _x( 'Belize', 'countries', 'personalizewp' ),
			'BJ' => _x( 'Benin', 'countries', 'personalizewp' ),
			'BM' => _x( 'Bermuda', 'countries', 'personalizewp' ),
			'BT' => _x( 'Bhutan', 'countries', 'personalizewp' ),
			'BO' => _x( 'Bolivia, Plurinational State of', 'countries', 'personalizewp' ),
			'BA' => _x( 'Bosnia and Herzegovina', 'countries', 'personalizewp' ),
			'BW' => _x( 'Botswana', 'countries', 'personalizewp' ),
			'BV' => _x( 'Bouvet Island', 'countries', 'personalizewp' ),
			'BR' => _x( 'Brazil', 'countries', 'personalizewp' ),
			'IO' => _x( 'British Indian Ocean Territory', 'countries', 'personalizewp' ),
			'BN' => _x( 'Brunei Darussalam', 'countries', 'personalizewp' ),
			'BG' => _x( 'Bulgaria', 'countries', 'personalizewp' ),
			'BF' => _x( 'Burkina Faso', 'countries', 'personalizewp' ),
			'BI' => _x( 'Burundi', 'countries', 'personalizewp' ),
			'KH' => _x( 'Cambodia', 'countries', 'personalizewp' ),
			'CM' => _x( 'Cameroon', 'countries', 'personalizewp' ),
			'CA' => _x( 'Canada', 'countries', 'personalizewp' ),
			'CV' => _x( 'Cape Verde', 'countries', 'personalizewp' ),
			'KY' => _x( 'Cayman Islands', 'countries', 'personalizewp' ),
			'CF' => _x( 'Central African Republic', 'countries', 'personalizewp' ),
			'TD' => _x( 'Chad', 'countries', 'personalizewp' ),
			'CL' => _x( 'Chile', 'countries', 'personalizewp' ),
			'CN' => _x( 'China', 'countries', 'personalizewp' ),
			'CX' => _x( 'Christmas Island', 'countries', 'personalizewp' ),
			'CC' => _x( 'Cocos (Keeling) Islands', 'countries', 'personalizewp' ),
			'CO' => _x( 'Colombia', 'countries', 'personalizewp' ),
			'KM' => _x( 'Comoros', 'countries', 'personalizewp' ),
			'CG' => _x( 'Congo', 'countries', 'personalizewp' ),
			'CD' => _x( 'Congo, Democratic Republic', 'countries', 'personalizewp' ),
			'CK' => _x( 'Cook Islands', 'countries', 'personalizewp' ),
			'CR' => _x( 'Costa Rica', 'countries', 'personalizewp' ),
			'CI' => _x( "Côte d'Ivoire", 'countries', 'personalizewp' ),
			'HR' => _x( 'Croatia', 'countries', 'personalizewp' ),
			'CU' => _x( 'Cuba', 'countries', 'personalizewp' ),
			'CY' => _x( 'Cyprus', 'countries', 'personalizewp' ),
			'CZ' => _x( 'Czech Republic', 'countries', 'personalizewp' ),
			'DK' => _x( 'Denmark', 'countries', 'personalizewp' ),
			'DJ' => _x( 'Djibouti', 'countries', 'personalizewp' ),
			'DM' => _x( 'Dominica', 'countries', 'personalizewp' ),
			'DO' => _x( 'Dominican Republic', 'countries', 'personalizewp' ),
			'EC' => _x( 'Ecuador', 'countries', 'personalizewp' ),
			'EG' => _x( 'Egypt', 'countries', 'personalizewp' ),
			'SV' => _x( 'El Salvador', 'countries', 'personalizewp' ),
			'GQ' => _x( 'Equatorial Guinea', 'countries', 'personalizewp' ),
			'ER' => _x( 'Eritrea', 'countries', 'personalizewp' ),
			'EE' => _x( 'Estonia', 'countries', 'personalizewp' ),
			'ET' => _x( 'Ethiopia', 'countries', 'personalizewp' ),
			'FK' => _x( 'Falkland Islands (Malvinas)', 'countries', 'personalizewp' ),
			'FO' => _x( 'Faroe Islands', 'countries', 'personalizewp' ),
			'FJ' => _x( 'Fiji', 'countries', 'personalizewp' ),
			'FI' => _x( 'Finland', 'countries', 'personalizewp' ),
			'FR' => _x( 'France', 'countries', 'personalizewp' ),
			'GF' => _x( 'French Guiana', 'countries', 'personalizewp' ),
			'PF' => _x( 'French Polynesia', 'countries', 'personalizewp' ),
			'TF' => _x( 'French Southern Territories', 'countries', 'personalizewp' ),
			'GA' => _x( 'Gabon', 'countries', 'personalizewp' ),
			'GM' => _x( 'Gambia', 'countries', 'personalizewp' ),
			'GE' => _x( 'Georgia', 'countries', 'personalizewp' ),
			'DE' => _x( 'Germany', 'countries', 'personalizewp' ),
			'GH' => _x( 'Ghana', 'countries', 'personalizewp' ),
			'GI' => _x( 'Gibraltar', 'countries', 'personalizewp' ),
			'GR' => _x( 'Greece', 'countries', 'personalizewp' ),
			'GL' => _x( 'Greenland', 'countries', 'personalizewp' ),
			'GD' => _x( 'Grenada', 'countries', 'personalizewp' ),
			'GP' => _x( 'Guadeloupe', 'countries', 'personalizewp' ),
			'GU' => _x( 'Guam', 'countries', 'personalizewp' ),
			'GT' => _x( 'Guatemala', 'countries', 'personalizewp' ),
			'GG' => _x( 'Guernsey', 'countries', 'personalizewp' ),
			'GN' => _x( 'Guinea', 'countries', 'personalizewp' ),
			'GW' => _x( 'Guinea-Bissau', 'countries', 'personalizewp' ),
			'GY' => _x( 'Guyana', 'countries', 'personalizewp' ),
			'HT' => _x( 'Haiti', 'countries', 'personalizewp' ),
			'HM' => _x( 'Heard Island & Mcdonald Islands', 'countries', 'personalizewp' ),
			'VA' => _x( 'Holy See (Vatican City State)', 'countries', 'personalizewp' ),
			'HN' => _x( 'Honduras', 'countries', 'personalizewp' ),
			'HK' => _x( 'Hong Kong', 'countries', 'personalizewp' ),
			'HU' => _x( 'Hungary', 'countries', 'personalizewp' ),
			'IS' => _x( 'Iceland', 'countries', 'personalizewp' ),
			'IN' => _x( 'India', 'countries', 'personalizewp' ),
			'ID' => _x( 'Indonesia', 'countries', 'personalizewp' ),
			'IR' => _x( 'Iran, Islamic Republic Of', 'countries', 'personalizewp' ),
			'IQ' => _x( 'Iraq', 'countries', 'personalizewp' ),
			'IE' => _x( 'Ireland', 'countries', 'personalizewp' ),
			'IM' => _x( 'Isle Of Man', 'countries', 'personalizewp' ),
			'IL' => _x( 'Israel', 'countries', 'personalizewp' ),
			'IT' => _x( 'Italy', 'countries', 'personalizewp' ),
			'JM' => _x( 'Jamaica', 'countries', 'personalizewp' ),
			'JP' => _x( 'Japan', 'countries', 'personalizewp' ),
			'JE' => _x( 'Jersey', 'countries', 'personalizewp' ),
			'JO' => _x( 'Jordan', 'countries', 'personalizewp' ),
			'KZ' => _x( 'Kazakhstan', 'countries', 'personalizewp' ),
			'KE' => _x( 'Kenya', 'countries', 'personalizewp' ),
			'KI' => _x( 'Kiribati', 'countries', 'personalizewp' ),
			'KP' => _x( "Korea, Democratic People's Republic of", 'countries', 'personalizewp' ),
			'KR' => _x( 'Korea, Republic of', 'countries', 'personalizewp' ),
			'KW' => _x( 'Kuwait', 'countries', 'personalizewp' ),
			'KG' => _x( 'Kyrgyzstan', 'countries', 'personalizewp' ),
			'LA' => _x( "Lao People's Democratic Republic", 'countries', 'personalizewp' ),
			'LV' => _x( 'Latvia', 'countries', 'personalizewp' ),
			'LB' => _x( 'Lebanon', 'countries', 'personalizewp' ),
			'LS' => _x( 'Lesotho', 'countries', 'personalizewp' ),
			'LR' => _x( 'Liberia', 'countries', 'personalizewp' ),
			'LY' => _x( 'Libyan Arab Jamahiriya', 'countries', 'personalizewp' ),
			'LI' => _x( 'Liechtenstein', 'countries', 'personalizewp' ),
			'LT' => _x( 'Lithuania', 'countries', 'personalizewp' ),
			'LU' => _x( 'Luxembourg', 'countries', 'personalizewp' ),
			'MO' => _x( 'Macao', 'countries', 'personalizewp' ),
			'MK' => _x( 'Macedonia, the former Yugoslav Republic of', 'countries', 'personalizewp' ),
			'MG' => _x( 'Madagascar', 'countries', 'personalizewp' ),
			'MW' => _x( 'Malawi', 'countries', 'personalizewp' ),
			'MY' => _x( 'Malaysia', 'countries', 'personalizewp' ),
			'MV' => _x( 'Maldives', 'countries', 'personalizewp' ),
			'ML' => _x( 'Mali', 'countries', 'personalizewp' ),
			'MT' => _x( 'Malta', 'countries', 'personalizewp' ),
			'MH' => _x( 'Marshall Islands', 'countries', 'personalizewp' ),
			'MQ' => _x( 'Martinique', 'countries', 'personalizewp' ),
			'MR' => _x( 'Mauritania', 'countries', 'personalizewp' ),
			'MU' => _x( 'Mauritius', 'countries', 'personalizewp' ),
			'YT' => _x( 'Mayotte', 'countries', 'personalizewp' ),
			'MX' => _x( 'Mexico', 'countries', 'personalizewp' ),
			'FM' => _x( 'Micronesia, Federated States Of', 'countries', 'personalizewp' ),
			'MD' => _x( 'Moldova, Republic of', 'countries', 'personalizewp' ),
			'MC' => _x( 'Monaco', 'countries', 'personalizewp' ),
			'MN' => _x( 'Mongolia', 'countries', 'personalizewp' ),
			'ME' => _x( 'Montenegro', 'countries', 'personalizewp' ),
			'MS' => _x( 'Montserrat', 'countries', 'personalizewp' ),
			'MA' => _x( 'Morocco', 'countries', 'personalizewp' ),
			'MZ' => _x( 'Mozambique', 'countries', 'personalizewp' ),
			'MM' => _x( 'Myanmar', 'countries', 'personalizewp' ),
			'NA' => _x( 'Namibia', 'countries', 'personalizewp' ),
			'NR' => _x( 'Nauru', 'countries', 'personalizewp' ),
			'NP' => _x( 'Nepal', 'countries', 'personalizewp' ),
			'NL' => _x( 'Netherlands', 'countries', 'personalizewp' ),
			'AN' => _x( 'Netherlands Antilles', 'countries', 'personalizewp' ),
			'NC' => _x( 'New Caledonia', 'countries', 'personalizewp' ),
			'NZ' => _x( 'New Zealand', 'countries', 'personalizewp' ),
			'NI' => _x( 'Nicaragua', 'countries', 'personalizewp' ),
			'NE' => _x( 'Niger', 'countries', 'personalizewp' ),
			'NG' => _x( 'Nigeria', 'countries', 'personalizewp' ),
			'NU' => _x( 'Niue', 'countries', 'personalizewp' ),
			'NF' => _x( 'Norfolk Island', 'countries', 'personalizewp' ),
			'MP' => _x( 'Northern Mariana Islands', 'countries', 'personalizewp' ),
			'NO' => _x( 'Norway', 'countries', 'personalizewp' ),
			'OM' => _x( 'Oman', 'countries', 'personalizewp' ),
			'PK' => _x( 'Pakistan', 'countries', 'personalizewp' ),
			'PW' => _x( 'Palau', 'countries', 'personalizewp' ),
			'PS' => _x( 'Palestinian Territory, Occupied', 'countries', 'personalizewp' ),
			'PA' => _x( 'Panama', 'countries', 'personalizewp' ),
			'PG' => _x( 'Papua New Guinea', 'countries', 'personalizewp' ),
			'PY' => _x( 'Paraguay', 'countries', 'personalizewp' ),
			'PE' => _x( 'Peru', 'countries', 'personalizewp' ),
			'PH' => _x( 'Philippines', 'countries', 'personalizewp' ),
			'PN' => _x( 'Pitcairn', 'countries', 'personalizewp' ),
			'PL' => _x( 'Poland', 'countries', 'personalizewp' ),
			'PT' => _x( 'Portugal', 'countries', 'personalizewp' ),
			'PR' => _x( 'Puerto Rico', 'countries', 'personalizewp' ),
			'QA' => _x( 'Qatar', 'countries', 'personalizewp' ),
			'RE' => _x( 'Réunion', 'countries', 'personalizewp' ),
			'RO' => _x( 'Romania', 'countries', 'personalizewp' ),
			'RU' => _x( 'Russian Federation', 'countries', 'personalizewp' ),
			'RW' => _x( 'Rwanda', 'countries', 'personalizewp' ),
			'BL' => _x( 'Saint Barthélemy', 'countries', 'personalizewp' ),
			'SH' => _x( 'Saint Helena, Ascension and Tristan da Cunha', 'countries', 'personalizewp' ),
			'KN' => _x( 'Saint Kitts and Nevis', 'countries', 'personalizewp' ),
			'LC' => _x( 'Saint Lucia', 'countries', 'personalizewp' ),
			'MF' => _x( 'Saint Martin (French part)', 'countries', 'personalizewp' ),
			'PM' => _x( 'Saint Pierre and Miquelon', 'countries', 'personalizewp' ),
			'VC' => _x( 'Saint Vincent and the Grenadines', 'countries', 'personalizewp' ),
			'WS' => _x( 'Samoa', 'countries', 'personalizewp' ),
			'SM' => _x( 'San Marino', 'countries', 'personalizewp' ),
			'ST' => _x( 'Sao Tome And Principe', 'countries', 'personalizewp' ),
			'SA' => _x( 'Saudi Arabia', 'countries', 'personalizewp' ),
			'SN' => _x( 'Senegal', 'countries', 'personalizewp' ),
			'RS' => _x( 'Serbia', 'countries', 'personalizewp' ),
			'SC' => _x( 'Seychelles', 'countries', 'personalizewp' ),
			'SL' => _x( 'Sierra Leone', 'countries', 'personalizewp' ),
			'SG' => _x( 'Singapore', 'countries', 'personalizewp' ),
			'SK' => _x( 'Slovakia', 'countries', 'personalizewp' ),
			'SI' => _x( 'Slovenia', 'countries', 'personalizewp' ),
			'SB' => _x( 'Solomon Islands', 'countries', 'personalizewp' ),
			'SO' => _x( 'Somalia', 'countries', 'personalizewp' ),
			'ZA' => _x( 'South Africa', 'countries', 'personalizewp' ),
			'GS' => _x( 'South Georgia And Sandwich Isl.', 'countries', 'personalizewp' ),
			'ES' => _x( 'Spain', 'countries', 'personalizewp' ),
			'LK' => _x( 'Sri Lanka', 'countries', 'personalizewp' ),
			'SD' => _x( 'Sudan', 'countries', 'personalizewp' ),
			'SR' => _x( 'Suriname', 'countries', 'personalizewp' ),
			'SJ' => _x( 'Svalbard And Jan Mayen', 'countries', 'personalizewp' ),
			'SZ' => _x( 'Swaziland', 'countries', 'personalizewp' ),
			'SE' => _x( 'Sweden', 'countries', 'personalizewp' ),
			'CH' => _x( 'Switzerland', 'countries', 'personalizewp' ),
			'SY' => _x( 'Syrian Arab Republic', 'countries', 'personalizewp' ),
			'TW' => _x( 'Taiwan', 'countries', 'personalizewp' ),
			'TJ' => _x( 'Tajikistan', 'countries', 'personalizewp' ),
			'TZ' => _x( 'Tanzania, United Republic of', 'countries', 'personalizewp' ),
			'TH' => _x( 'Thailand', 'countries', 'personalizewp' ),
			'TL' => _x( 'Timor-Leste', 'countries', 'personalizewp' ),
			'TG' => _x( 'Togo', 'countries', 'personalizewp' ),
			'TK' => _x( 'Tokelau', 'countries', 'personalizewp' ),
			'TO' => _x( 'Tonga', 'countries', 'personalizewp' ),
			'TT' => _x( 'Trinidad And Tobago', 'countries', 'personalizewp' ),
			'TN' => _x( 'Tunisia', 'countries', 'personalizewp' ),
			'TR' => _x( 'Turkey', 'countries', 'personalizewp' ),
			'TM' => _x( 'Turkmenistan', 'countries', 'personalizewp' ),
			'TC' => _x( 'Turks And Caicos Islands', 'countries', 'personalizewp' ),
			'TV' => _x( 'Tuvalu', 'countries', 'personalizewp' ),
			'UG' => _x( 'Uganda', 'countries', 'personalizewp' ),
			'UA' => _x( 'Ukraine', 'countries', 'personalizewp' ),
			'AE' => _x( 'United Arab Emirates', 'countries', 'personalizewp' ),
			'GB' => _x( 'United Kingdom', 'countries', 'personalizewp' ),
			'US' => _x( 'United States', 'countries', 'personalizewp' ),
			'UM' => _x( 'United States Outlying Islands', 'countries', 'personalizewp' ),
			'UY' => _x( 'Uruguay', 'countries', 'personalizewp' ),
			'UZ' => _x( 'Uzbekistan', 'countries', 'personalizewp' ),
			'VU' => _x( 'Vanuatu', 'countries', 'personalizewp' ),
			'VE' => _x( 'Venezuela, Bolivarian Republic of', 'countries', 'personalizewp' ),
			'VN' => _x( 'Viet Nam', 'countries', 'personalizewp' ),
			'VG' => _x( 'Virgin Islands, British', 'countries', 'personalizewp' ),
			'VI' => _x( 'Virgin Islands, U.S.', 'countries', 'personalizewp' ),
			'WF' => _x( 'Wallis and Futuna', 'countries', 'personalizewp' ),
			'EH' => _x( 'Western Sahara', 'countries', 'personalizewp' ),
			'YE' => _x( 'Yemen', 'countries', 'personalizewp' ),
			'ZM' => _x( 'Zambia', 'countries', 'personalizewp' ),
			'ZW' => _x( 'Zimbabwe', 'countries', 'personalizewp' ),
		];
	}

	/**
	 * Returns the filesystem path for a file/dir within this plugin.
	 *
	 * @since 2.5.0
	 * @param string $file The path within this plugin, e.g. '/includes/integrations'
	 *
	 * @return string Filesystem path
	 */
	public function plugin_path( $file = '' ) {
		return $this->locations['dir'] . ltrim( $file, '/' );
	}

	/**
	 * Returns the URL path for a file/dir within this plugin.
	 *
	 * @since 2.6.0
	 * @param string $file The path within this plugin, e.g. '/public/js'
	 *
	 * @return string Filesystem path
	 */
	public function plugin_url( $file = '' ) {
		return $this->locations['url'] . ltrim( $file, '/' );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_personalizewp() {
		return $this->personalizewp;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
