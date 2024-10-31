<?php
/**
 * Admin utilities.
 *
 * @link  https://personalizewp.com
 * @since 2.0.0
 *
 * @package PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Admin utilities.
 *
 * Functions that don't have a better location
 */
class Utils {

	/**
	 * Store the used icons to output in the footer
	 *
	 * @var array
	 */
	public static $track_used_icons = [];

	/**
	 * Echos the contents of an SVG for use inline.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $name The SVG Name.
	 * @return void
	 */
	public static function svg_icon( $name = '' ) {
		$icon = self::get_svg_icon( $name );
		if ( ! empty( $icon ) ) {
			// Very limited svg reference, so can sanitize.
			echo wp_kses(
				$icon,
				array(
					'svg' => array(
						'width'  => true,
						'height' => true,
					),
					'use' => array(
						'xlink:href' => true,
					),
				)
			);
		}
	}

	/**
	 * Returns the contents of an SVG for use inline.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $name The SVG Name.
	 * @return string
	 */
	public static function get_svg_icon( $name = '' ) {
		// Nothing to return.
		if ( empty( $name ) ) {
			return '';
		}
		// Unknown svg, just return
		if ( isset( self::$track_used_icons[ $name ] ) && empty( self::$track_used_icons[ $name ]['icon'] ) ) {
			return '';
		}
		// Check for pre-process
		if ( empty( self::$track_used_icons[ $name ]['icon'] ) ) {
			$svg      = '';
			$svg_file = __DIR__ . '/img/' . esc_attr( $name ) . '.svg';

			// Check and suppress PHP errors for non-existent file.
			if ( ! file_exists( $svg_file ) ) {
				// Suppress further checks on non-existant icon.
				self::$track_used_icons[ $name ] = '';
				return '';
			}

			$contents = file_get_contents( $svg_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reason: local file
			// Remove XML as unnessary when embedding in HTML.
			$contents = str_replace( '<?xml version="1.0" encoding="UTF-8"?>', '', $contents );

			$tags = new \WP_HTML_Tag_Processor( $contents );
			$tags->next_tag( 'svg' );
			$svg = array(
				'icon'   => $contents,
				'width'  => $tags->get_attribute( 'width' ),
				'height' => $tags->get_attribute( 'height' ),
			);
			// Store for all future uses.
			self::$track_used_icons[ $name ] = $svg;
		}

		// For now just return a SVG reference
		return sprintf(
			'<svg width="%2$s" height="%3$s"><use xlink:href="#pwp-icon-%1$s" /></svg>',
			esc_attr( sanitize_key( $name ) ),
			esc_attr( self::$track_used_icons[ $name ]['width'] ),
			esc_attr( self::$track_used_icons[ $name ]['height'] )
		);
	}

	/**
	 * Outputs the inline SVGs that have been used.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function output_inline_svgs() {
		if ( empty( self::$track_used_icons ) ) {
			return;
		}

		$inline_svgs = [];
		foreach ( self::$track_used_icons as $icon_name => $icon_data ) {
			if ( empty( $icon_data['icon'] ) ) {
				continue;
			}
			$symbol = $icon_data['icon'];

			// Symbols don't have height/width, so remove them.
			$symbol = str_replace(
				array(
					'<svg ',
					'</svg>',
					sprintf(
						' height="%1$s"',
						$icon_data['height']
					),
					sprintf(
						' width="%1$s"',
						$icon_data['width']
					),
				),
				array(
					sprintf(
						'<symbol id="pwp-icon-%1$s" ',
						esc_attr( sanitize_key( $icon_name ) )
					),
					'</symbol>',
					'',
					'',
				),
				$symbol
			);

			$inline_svgs[] = $symbol;
		}

		if ( empty( $inline_svgs ) ) {
			return;
		}

		// Output the HTML
		printf(
			'<!-- PersonalizeWP Icons --><svg xmlns="http://www.w3.org/2000/svg" style="display: none;">%1$s</svg>',
			implode( '', $inline_svgs ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: own svgs and no built in sanitising for svgs
		);
	}
}
