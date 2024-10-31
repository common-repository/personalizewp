<?php
/**
 * Provide an admin view for editing an individual Rule
 *
 * @since      1.0.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin/Partials
 */

namespace PersonalizeWP\Admin\Partials;

use PersonalizeWP\Admin\ListTableRuleUsage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>

	<section class="section mb-0 wp-dxp-body-content">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<a class="back mb-3" href="<?php echo esc_url( PERSONALIZEWP_ADMIN_RULES_INDEX_URL ); ?>">&larr; <?php esc_html_e( 'Back', 'personalizewp' ); ?></a>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<?php
					if ( ! $rule->is_usable ) :
						?>
						<div class="alert alert-danger">
							<p><?php esc_html_e( 'This rule is not currently usable for the following reasons:', 'personalizewp' ); ?></p>
							<ul>
								<li>
								<?php
								echo implode( '</li><li>', array_map( 'esc_html', $rule->getConditionDependencyIssues() ) );
								?>
								</li>
							</ul>
						</div>
						<?php
					endif;
					?>
				</div>
			</div>
			<div class="row mb-lg-2">
				<div class="col-8 col-md-10 col-lg-8 d-inline-flex align-items-center justify-content-between">
					<h2 class="h4 section-title mb-2"><?php esc_html_e( 'View / edit rule', 'personalizewp' ); ?></h2>
				</div>
				<div class="col-4 col-md-2 d-inline-flex align-items-center justify-content-end">
					<?php if ( $rule->can_duplicate ) : ?>
						<a class="d-inline-block mr-3 mr-md-5 contextual-link" href="<?php echo esc_url( wp_nonce_url( $rule->duplicate_url, 'rule-duplicate-' . $rule->id ) ); ?>"><?php esc_html_e( 'Duplicate', 'personalizewp' ); ?></a>
					<?php else : ?>
						<span class="d-inline-block mr-3 mr-md-5 contextual-link text-muted"><?php esc_html_e( 'Duplicate', 'personalizewp' ); ?></span>
					<?php endif; ?>

					<?php if ( $rule->can_delete ) : ?>
						<a class="d-inline-block contextual-link delete-rule" href="<?php echo esc_url( wp_nonce_url( $rule->delete_url, 'rule-delete-' . $rule->id ) ); ?>"
							data-show-modal="#deleteRuleModal"><?php esc_html_e( 'Delete', 'personalizewp' ); ?></a>
					<?php else : ?>
						<span class="d-inline-block contextual-link text-muted"><?php esc_html_e( 'Delete', 'personalizewp' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div class="row mb-lg-4">
				<div class="col-12 section-description">
					<p><?php esc_html_e( 'Edit an existing rule using the options below. Once the rule has been saved, it will become active or inactive, depending on the conditions chosen. All conditions within a single rule are matched using AND logic.', 'personalizewp' ); ?></p>
				</div>
			</div>
		</div>
	</section>



	<section class="section">
		<div class="container-fluid">
			<div class="row mb-lg-2">
				<div class="col-12 col-lg-10">

					<form action="<?php echo esc_url( $rule->edit_url ); ?>" method="post" accept-charset="UTF-8" id="wp-dxp-form">
						<?php
							wp_nonce_field( 'rule-update-' . $rule->id );
							require_once '_form.php';
						?>
					</form>

				</div>
			</div>
		</div>
	</section>


	<section class="section mt-5 pt-4">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12 col-lg-10">
					<?php
					$rule_usage = new ListTableRuleUsage();
					$rule_usage->prepare_items();
					?>
					<form id="rule-usage-filter" method="get">
						<div class="with-sidebar rule-usage" data-direction="rtl">
							<h2 class="h4 section-subtitle mb-0"><?php esc_html_e( 'Current usage', 'personalizewp' ); ?></h2>

							<div class="sidebar table-actions">
								<?php
								$rule_usage->search_box( __( 'Search usage', 'personalizewp' ), 'search-usage' );
								?>
							</div>
						</div>

						<?php
						$rule_usage->display();
						?>
					</form>
				</div>
			</div>
		</div>
	</section>

<?php
require_once '_modal-delete.php';
