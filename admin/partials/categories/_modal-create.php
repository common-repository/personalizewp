<?php
/**
 * Create Category modal template
 *
 * @since      1.2.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

use PersonalizeWP_Category;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<dialog id="createCategoryModal" class="dialog">
	<form action="<?php echo esc_url( PERSONALIZEWP_ADMIN_CATEGORIES_CREATE_URL ); ?>" method="post" accept-charset="UTF-8" class="pwp-form flow">
		<div class="dialog-header">
			<h5 class="dialog-title"><?php esc_html_e( 'Add New Category', 'personalizewp' ); ?></h5>
			<button type="button" formnovalidate value="close" formmethod="dialog">
				<span aria-hidden="true">&times;</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close', 'personalizewp' ); ?></span>
			</button>
		</div>
		<?php
			wp_nonce_field( 'category-create' );
			// Set for the form.
			$category   = new PersonalizeWP_Category();
			$modal_mode = true;
			require '_form.php';
		?>
	</form>
</dialog>
