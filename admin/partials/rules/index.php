<?php
/**
 * Provide an admin view for listing Rules
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

<?php require_once plugin_dir_path( __DIR__ ) . '/_other/personalization-nav.php'; ?>

<section class="section wp-dxp-body-content">
	<div class="container-fluid">
		<?php
		$rules = new ListTableRules();
		$rules->prepare_items();
		?>
		<form id="rules-filter" method="get">
			<div class="with-sidebar rules" data-direction="rtl">
				<div class="section-description">
					<p><?php esc_html_e( 'Rules that are active will be displayed to a user and are available to use. If a rule is inactive, content related to it will not currently be displayed to a user. Reasons these rules are "inactive" include dependence upon a third party plugin to function (for example WooCommerce).', 'personalizewp' ); ?></p>
				</div>

				<div class="sidebar table-actions">
					<a class="primary btn create" href="<?php echo esc_url( wp_nonce_url( PERSONALIZEWP_ADMIN_RULES_CREATE_URL, 'rule-create' ) ); ?>"><?php esc_html_e( 'Create rule', 'personalizewp' ); ?></a>
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

<?php
require_once '_modal-delete.php';
