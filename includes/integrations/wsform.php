<?php
/**
 * WS Form Integration
 *
 * @link https://personalizewp.com/
 * @since 2.5.0
 *
 * @package PersonalizeWP
 * @subpackage PersonalizeWP/Integrations
 */

namespace PersonalizeWP\Integrations\WSForm;

/**
 * Register the WS Form integation.
 *
 * @return void
 */
function register_integration() {
	add_filter(
		'wsf_enqueue_scripts',
		function () {
			// Add JS to the footer when any WS Form is in use.
			add_action( 'wp_footer', __NAMESPACE__ . '\action_wp_footer' );
		}
	);
}
add_action( 'personalizewp_register_integrations', __NAMESPACE__ . '\register_integration' );

/**
 * Output inline JS that ensures initialisation of any injected WS Form.
 *
 * @return void
 */
function action_wp_footer() {
	?>
<script>
document.addEventListener('PWP:parsedPlaceholders', () => { if ( typeof wsf_form_init === "function" ) { wsf_form_init() } } );
</script>
	<?php
}
