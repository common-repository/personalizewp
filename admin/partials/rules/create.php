<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 * This file should primarily consist of HTML with a little bit of PHP.
 *
 * @link       https://personalizewp.com
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

	<section class="section mb-0 wp-dxp-body-content">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<a class="back mb-3" href="<?php echo esc_url( PERSONALIZEWP_ADMIN_RULES_INDEX_URL ); ?>">&larr; <?php esc_html_e( 'Back', 'personalizewp' ); ?></a>
				</div>
			</div>
			<div class="row mb-lg-2">
				<div class="col-12 d-inline-flex align-items-center justify-content-between">
					<h2 class="h4 section-title mb-2"><?php esc_html_e( 'Add New Rule', 'personalizewp' ); ?></h2>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-12 section-description">
					<p><?php esc_html_e( 'Create a new rule using the options below. Once the rule has been added, it will become active or inactive, depending on the conditions chosen. All conditions within a single rule are matched using AND logic.', 'personalizewp' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="container-fluid">
			<div class="row mb-lg-2">
				<div class="col-12 col-lg-10">

					<form action="<?php echo esc_url( PERSONALIZEWP_ADMIN_RULES_CREATE_URL ); ?>" method="post" accept-charset="UTF-8" id="wp-dxp-form">
						<?php
							wp_nonce_field( 'rule-create' );
							require_once '_form.php';
						?>
					</form>

				</div>
			</div>
		</div>
	</section>
