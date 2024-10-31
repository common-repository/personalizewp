<?php
/**
 * PersonalizeWP Autoloader.
 *
 * Supports files being located within directories matching their namespace
 * from the root directory of the plugin, as well as within the /includes/ dir.
 *
 * @since   1.2.0
 *
 * @package PersonalizeWP
 */

namespace PersonalizeWP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Namespace prefix to use.
	 *
	 * @var string
	 */
	private $prefix = '';

	/**
	 * Path to the base directory of the plugin.
	 *
	 * @var string
	 */
	private $base_path = '';

	/**
	 * Path to the general /includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 *
	 * @param string $dir Plugin root directory
	 */
	public function __construct( $dir ) {

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->prefix       = __NAMESPACE__ . '\\';
		$this->base_path    = $dir . '/';
		$this->include_path = $dir . '/includes/';
	}

	/**
	 * Get the base sub-namespace of a class and turn it into a path.
	 *
	 * @param  string $class_namespace Namespace.
	 * @return string
	 */
	private function get_file_path_from_namespace( $class_namespace ) {

		$class_namespace = \str_replace( array( '\\', '_' ), array( '/', '-' ), \strtolower( $class_namespace ) );
		if ( ! empty( $class_namespace ) ) {
			$class_namespace = \trailingslashit( $class_namespace );
		}
		return $class_namespace;
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class_name Class name.
	 * @return string
	 */
	private function get_file_name_from_class( $class_name ) {
		return 'class-' . \str_replace( '_', '-', \strtolower( $class_name ) ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path File path.
	 * @return bool Successful or not.
	 */
	private function load_file( $path ) {
		if ( $path && \is_readable( $path ) ) {
			require_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load classes on demand to reduce memory consumption.
	 *
	 * @param string $class_name Full class name including namespace.
	 */
	public function autoload( $class_name ) {

		// Exclude other namespaces.
		if ( ! \str_starts_with( $class_name, $this->prefix ) || \str_starts_with( $class_name, $this->prefix . 'Pro\\' ) ) {
			return;
		}

		// Exclude the main namespace.
		$class_name = \str_replace( $this->prefix, '', $class_name );

		$path = '';
		// Check for sub-namespaces/sub-directories.
		if ( str_contains( $class_name, '\\' ) && preg_match( '/^(?P<namespace>.+)\\\\(?P<class>[^\\\\]+)$/', $class_name, $matches ) ) {
			$path       = $this->get_file_path_from_namespace( $matches['namespace'] );
			$class_name = $matches['class'];
		}
		$file = $this->get_file_name_from_class( $class_name );

		if ( ! str_starts_with( $path, 'admin' ) || ! $this->load_file( $this->base_path . $path . $file ) ) {
			$this->load_file( $this->include_path . $path . $file );
		}
	}
}
