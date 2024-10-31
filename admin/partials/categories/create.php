<?php
/**
 * Provide an admin view for Category creation
 *
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

<section class="section mb-0 wp-dxp-body-content">
	<div class="container-fluid">
		<div class="row mb-lg-2">
			<div class="col-12 d-inline-flex align-items-center justify-content-between">
				<h2 class="h4 section-title mb-2"><?php esc_html_e( 'Add New Category', 'personalizewp' ); ?></h2>
			</div>
		</div>
		<div class="row mb-3">
			<div class="col-12 section-description">
				<p><?php esc_html_e( 'Create a new category using the options below.', 'personalizewp' ); ?></p>
			</div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container-fluid">
		<div class="row mb-lg-2">
			<div class="col-12 col-lg-10">

				<form action="<?php echo esc_url( PERSONALIZEWP_ADMIN_CATEGORIES_CREATE_URL ); ?>" method="post" accept-charset="UTF-8" class="pwp-form flow">
					<?php
					wp_nonce_field( 'category-create' );
					$modal_mode = false;
					require_once '_form.php';
					?>
				</form>

			</div>
		</div>
	</div>
</section>
