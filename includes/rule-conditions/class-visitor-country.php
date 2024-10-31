<?php
/**
 * Specific Country type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions
 */

namespace PersonalizeWP\Rule_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks the current IP address of the user against known country conditions
 */
class Visitor_Country extends RuleCondition {

	private $personalizewp;

	public string $identifier = 'core_visitor_country';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Core', 'personalizewp' );

		$this->description = __( 'User Location', 'personalizewp' );

		$this->comparators = [
			'equals'         => _x( 'Is Equal To', 'Comparator', 'personalizewp' ),
			'does_not_equal' => _x( 'Does Not Equal', 'Comparator', 'personalizewp' ),
		];

		$this->personalizewp = \personalizewp();

		$this->comparison_values = $this->personalizewp->get_countries();
	}

	/**
	 * Returns the known IP address of the visitor
	 *
	 * @since 2.5.0
	 *
	 * @return string|false
	 */
	private function get_ip_address() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : false;
	}

	/**
	 * Test data against condition
	 *
	 * @param  string $comparator
	 * @return bool
	 */
	public function matchesCriteria( $comparator, $value, $action, $meta = [] ) : bool {
		switch ( $comparator ) {
			case 'equals':
				return $this->comparatorEquals( $value );
			case 'does_not_equal':
				return $this->comparatorDoesNotEqual( $value );
			case 'any':
				return $this->comparatorAny( $value );
		}

		return false;
	}

	/**
	 * "Equal" test
	 *
	 * @return bool
	 */
	public function comparatorEquals( $value ) {
		$remote_addr_header_ip = $this->get_ip_address();
		// Return if REMOTE_ADDR is not set.
		if ( empty( $remote_addr_header_ip ) ) {
			return false;
		}

		$isocode = $this->personalizewp->convert_ip_to_isocode( $remote_addr_header_ip );
		return $isocode === $value;
	}

	/**
	 * "Does not equal" test
	 *
	 * @return bool
	 */
	public function comparatorDoesNotEqual( $value ) {
		$remote_addr_header_ip = $this->get_ip_address();
		// Return if REMOTE_ADDR is not set.
		if ( empty( $remote_addr_header_ip ) ) {
			return false;
		}

		$isocode = $this->personalizewp->convert_ip_to_isocode( $remote_addr_header_ip );
		return $isocode !== $value;
	}

	/**
	 * "Any" test
	 *
	 * @return bool
	 */
	public function comparatorAny( $value ) {
		$remote_addr_header_ip = $this->get_ip_address();
		// Return if REMOTE_ADDR is not set.
		if ( empty( $remote_addr_header_ip ) ) {
			return false;
		}

		$isocode = $this->personalizewp->convert_ip_to_isocode( $remote_addr_header_ip );
		return in_array( $isocode, $value, true );
	}
}
