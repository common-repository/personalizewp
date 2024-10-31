<?php
/**
 * Provide an admin view for Category editing
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
				<h2 class="h4 section-title mb-2">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: %s category name. */
							__( 'Edit Category: %s', 'personalizewp' ),
							$category->name
						)
					);
					?>
				</h2>
			</div>
		</div>
		<div class="row mb-2">
			<div class="col-12 section-description">
				<p><?php esc_html_e( 'Edit the category details using the form below. When finished, click on Save to update the category.', 'personalizewp' ); ?></p>
			</div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12 col-lg-10">

				<form action="<?php echo esc_url( $category->edit_url ); ?>" method="post" accept-charset="UTF-8" class="pwp-form flow">
					<?php
						wp_nonce_field( 'category-update-' . $category->id );
						$modal_mode = false;
						require_once '_form.php';
					?>
				</form>

			</div>
		</div>
	</div>
</section>
