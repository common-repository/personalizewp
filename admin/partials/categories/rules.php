<?php
/**
 * Provide an admin view for listing Rules within a Category
 *
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

use PersonalizeWP\Admin\ListTableRules;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

<section class="section mb-0 wp-dxp-body-content">
	<div class="container-fluid">
		<?php
		$rules = new ListTableRules();
		$rules->prepare_items();
		?>
		<form id="rules-filter" method="get">
			<div class="with-sidebar rules" data-direction="rtl">
				<div class="section-description">
					<h2 class="h4 section-title mb-2"><?php esc_html_e( 'Rules', 'personalizewp' ); ?> - <?php echo esc_html( $category->name ); ?></h2>

					<p><?php esc_html_e( 'Rules that are active will be displayed to a user and are available to use. Click on View/Edit to find out more information about the rule.', 'personalizewp' ); ?></p>
				</div>

				<div class="sidebar table-actions">
					<?php
					$rules->search_box( __( 'Search rules', 'personalizewp' ), 'search-rules' );
					?>
				</div>
			</div>

			<?php
			$rules->display();
			?>
		</form>
	</div>
</section>
