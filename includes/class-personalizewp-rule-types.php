<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class PersonalizeWP_Rule_Types {

	public static $STANDARD = 'standard';
	public static $CUSTOM   = 'custom';

	public static function getAll() {
		return [
			[
				'id'   => self::$STANDARD,
				'name' => esc_html_x( 'Standard', 'Rule type', 'personalizewp' ),
			],
			[
				'id'   => self::$CUSTOM,
				'name' => esc_html_x( 'Custom', 'Rule type', 'personalizewp' ),
			],
		];
	}

	public static function getName( $id ) {
		foreach ( self::getAll() as $item ) {
			if ( $item['id'] == $id ) {
				return $item['name'];
			}
		}

		return esc_html_x( 'Unknown', 'Rule type', 'personalizewp' );
	}
}
