<?php
/**
 * Singleton Trait
 *
 * @link       https://personalizewp.com/
 * @since      2.5.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Traits
 */

namespace PersonalizeWP\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait SingletonTrait {

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instances = [];

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {

		$class = get_called_class();
		if ( ! isset( static::$instances[ $class ] ) ) {
			static::$instances[ $class ] = new $class();
		}
		return static::$instances[ $class ];
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	final public function __wakeup() {
		\_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'personalizewp' ), '2.5' );
		die();
	}
}
