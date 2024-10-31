<?php
/**
 * Delete Category modal template
 *
 * @since      1.2.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<dialog id="deleteCategoryModal" data-url-action class="dialog">
	<form action="" method="post" accept-charset="UTF-8" class="pwp-form flow">
		<div class="dialog-header">
			<h5 class="dialog-title"><?php esc_html_e( 'Delete category?', 'personalizewp' ); ?></h5>
			<button formnovalidate value="close" type="button" formmethod="dialog">
				<span aria-hidden="true">&times;</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close', 'personalizewp' ); ?></span>
			</button>
		</div>
		<p><?php esc_html_e( 'Are you sure you want to delete this category?', 'personalizewp' ); ?></p>
		<div class="actions">
			<button type="submit" class="primary btn"><?php esc_html_e( 'Delete', 'personalizewp' ); ?></button>
			<button type="button" class="secondary btn" formnovalidate value="close" formmethod="dialog"><?php esc_html_e( 'Cancel', 'personalizewp' ); ?></button>
		</div>
	</form>
</dialog>
