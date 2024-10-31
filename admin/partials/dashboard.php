<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 * This file should primarily consist of HTML with a little bit of PHP
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

<main class="dashboard">

	<h2 class="dashboard-title"><?php esc_html_e( 'Features', 'personalizewp' ); ?></h2>
	<p class="dashboard-description"><?php esc_html_e( 'The base plugin includes all of our display conditions for showing and hiding your content blocks.', 'personalizewp' ); ?></p>

	<section class="pwp-tiles grid">
	<?php
		foreach ( $tiles as $tile ) :
			include plugin_dir_path( __FILE__ ) . '_other/tile.php';
		endforeach;
	?>
	</section>

	<h2 class="dashboard-title"><?php esc_html_e( 'Pro Features', 'personalizewp' ); ?></h2>
	<p class="dashboard-description"><?php esc_html_e( 'The Pro version of PersonalizeWP offers additional functionality to track your visitors, segment them into groups, add lead scoring, and control access to blocks.', 'personalizewp' ); ?></p>

	<section class="pwp-tiles grid">
	<?php
		foreach ( $tiles_pro as $tile ) :
			include plugin_dir_path( __FILE__ ) . '_other/tile.php';
		endforeach;
	?>
	</section>

</main>
