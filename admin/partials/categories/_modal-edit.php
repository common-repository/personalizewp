<?php
/**
 * Edit Category modal template
 *
 * @since      1.2.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<dialog id="editCategoryModal" class="dialog">
	<form action="" method="post" accept-charset="UTF-8" class="pwp-form flow">
		<div class="dialog-header">
			<h5 class="dialog-title"><?php esc_html_e( 'Edit Category', 'personalizewp' ); ?></h5>
			<button type="button" formnovalidate value="close" formmethod="dialog">
				<span aria-hidden="true">&times;</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close', 'personalizewp' ); ?></span>
			</button>
		</div>
		<?php
		// Nonce included as part of edit URL action, set via JS using href of link
		require '_form.php';
		?>
	</form>
</dialog>
