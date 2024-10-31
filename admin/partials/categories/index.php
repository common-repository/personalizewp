<?php
/**
 * Provide an admin view for Rule Categories
 *
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

use PersonalizeWP\Admin\ListTableCategories;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

<?php require_once plugin_dir_path( __DIR__ ) . '/_other/personalization-nav.php'; ?>

<section class="section wp-dxp-body-content">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<?php
				$categories = new ListTableCategories();
				$categories->prepare_items();
				?>
				<form id="categories-filter" method="get">
					<div class="with-sidebar categories" data-direction="rtl">
						<div class="section-description">
							<p><?php esc_html_e( 'PersonalizeWP provides a number of pre-built rules and categories that are ready to use on your site, which you can see below. If you want to add your own, just click on the Create Rule or Create Category button.', 'personalizewp' ); ?></p>
						</div>

						<div class="sidebar table-actions">
							<a class="primary btn create" data-show-modal="#createCategoryModal" href="<?php echo esc_url( PERSONALIZEWP_ADMIN_CATEGORIES_CREATE_URL ); ?>"><?php esc_html_e( 'Create category', 'personalizewp' ); ?></a>
							<?php
							$categories->search_box( __( 'Search categories', 'personalizewp' ), 'search-categories' );
							?>
						</div>
					</div>

					<?php
					$categories->display();
					?>
				</form>

			</div>
		</div>
	</div>
</section>

<?php
require_once '_modal-create.php';
require_once '_modal-edit.php';
require_once '_modal-delete.php';
