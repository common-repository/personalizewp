<?php

use \PersonalizeWP\Rules_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( $this->getError( 'form' ) ) : ?>
<div class="alert alert-danger" role="alert">
	<?php
	echo implode( '<br>', array_map( 'esc_html', $this->getError( 'form' ) ) );
	?>
</div>
	<?php
endif;
?>

<input type="hidden" name="personalizewp_form[id]" value="<?php echo esc_attr( $rule->id ); ?>" />

<div class="row mb-4">
	<div class="col-12 col-lg-6">
		<div class="form-group mb-2">
			<label for="personalizewp_form[name]"><?php esc_html_e( 'Rule Name', 'personalizewp' ); ?></label>
			<input type="text" required id="personalizewp_form[name]" name="personalizewp_form[name]" value="<?php echo esc_attr( $rule->name ); ?>"
				class="form-control <?php echo ( $this->getError( 'name' ) ? esc_attr( 'is-invalid' ) : '' ); ?>" />
			<?php
			if ( $this->getError( 'name' ) ) :
				?>
			<div class="invalid-feedback">
				<span class="dashicons dashicons-warning"></span>
				<?php echo esc_html( implode( ', ', $this->getError( 'name' ) ) ); ?>
			</div>
				<?php
			endif;
			?>
		</div>
	</div>
</div>


<div class="row mb-4">
	<div class="col-12 col-lg-6">
		<div class="form-group mb-2 category-dropdown">
			<label for="personalizewp_form[category_id]"><?php esc_html_e( 'Category', 'personalizewp' ); ?></label>

			<select id="personalizewp_form[category_id]" name="personalizewp_form[category_id]" class="chosen-select form-control <?php echo ( $this->getError( 'category_id' ) ? esc_attr( 'is-invalid' ) : '' ); ?>">
				<?php
				$options = [ '' => __( '-- Select a category --', 'personalizewp' ) ] + PersonalizeWP_Category::list();
				foreach ( $options as $opt_val => $opt_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $opt_val ),
						selected( (int) $rule->category_id === (int) $opt_val, true, false ),
						esc_html( $opt_label )
					);
				}
				?>
			</select>

			<?php
			if ( $this->getError( 'category_id' ) ) :
				?>
				<div class="invalid-feedback">
					<span class="dashicons dashicons-warning"></span>
					<?php echo esc_html( implode( ', ', $this->getError( 'category_id' ) ) ); ?>
				</div>
				<?php
				endif;
			?>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-12 col-xl-10">
		<div class="form-group mb-2">
			<label><?php esc_html_e( 'Conditions', 'personalizewp' ); ?></label>
			<p><?php esc_html_e( 'All conditions within a single rule are matched using AND logic. This means that ALL conditions have to be met for the rule to be True. If you have multiple conditions and one condition is not met, the rule will be False. There is no limit to the number of conditions you can add.', 'personalizewp' ); ?></p>

			<?php
			// Used repeatedly
			$all_grouped_conditions_options = [ '' => __( '-- Select a measure --', 'personalizewp' ) ] + Rules_Conditions::grouped_list();
			?>
			<div id="conditions-container" class="conditions" data-condition-count="<?php echo (int) count( $rule->conditions ); ?>">
				<?php
				if ( $rule->conditions ) :
					foreach ( $rule->conditions as $i => $condition ) :
						?>

						<?php
							// Use the identifier to find the corresponding class
							$condition_class = Rules_Conditions::get_class( $condition->measure );
							// But then use the classes' identifier going forwards, in case of mapping
							$condition_identifier = ! empty( $condition_class->identifier ) ? $condition_class->identifier : $condition->measure;

							$condition_measure_key = ! is_null( $condition_class ) ? $condition_class->measure_key : null;
							$condition_meta_value  = ! is_null( $condition_measure_key ) && ! empty( $condition->meta->$condition_measure_key ) ? $condition->meta->$condition_measure_key : '';

							// Used repeatedly
							$condition_type                = ! is_null( $condition_class ) && method_exists( $condition_class, 'getComparisonType' ) ? $condition_class->getComparisonType() : '';
							$condition_comparator_opts     = [ '' => __( '-- Select a comparator --', 'personalizewp' ) ];
							if ( ! is_null( $condition_class ) && method_exists( $condition_class, 'getComparators' ) ) {
								$condition_comparator_opts = $condition_comparator_opts + $condition_class->getComparators();
							}
							$condition_comparison_val_opts = [ '' => __( '-- Select a value --', 'personalizewp' ) ];
							if ( ! is_null( $condition_class ) && method_exists( $condition_class, 'getComparisonValues' ) ) {
								$condition_comparison_val_opts = $condition_comparison_val_opts + $condition_class->getComparisonValues();
							}
						?>

						<div data-condition="row" class="condition mb-3 mb-sm-3">

							<select name="personalizewp_form[conditions][measure][]" class="conditions-measure chosen-select">
								<?php
								foreach ( $all_grouped_conditions_options as $opt_val => $opt_label ) {
									if ( is_array( $opt_label ) ) {
										printf( '<optgroup label="%s">', esc_attr( $opt_val ) );
										foreach ( $opt_label as $sub_opt_val => $sub_opt_label ) {
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												esc_attr( $sub_opt_val ),
												selected( $condition_identifier === $sub_opt_val, true, false ),
												esc_html( $sub_opt_label )
											);
										}
										echo '</optgroup>';
									} else {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											esc_attr( $opt_val ),
											selected( $condition_identifier === $opt_val, true, false ),
											esc_html( $opt_label )
										);
									}
								}
								?>
							</select>

							<div class="meta-text-field-wrapper field-wrapper-styles meta-value-wrapper">
								<input type="text" name="personalizewp_form[conditions][meta_value][]" value="<?php echo esc_attr( $condition_meta_value ); ?>"
									class="text-field field-meta-value-text" placeholder="<?php esc_attr_e( '-- Enter cookie name here --', 'personalizewp' ); ?>" autocomplete="off" />
							</div>

							<select name="personalizewp_form[conditions][comparator][]" class="conditions-comparator chosen-select">
								<?php
								foreach ( $condition_comparator_opts as $opt_val => $opt_label ) {
									printf(
										'<option value="%1$s" %2$s>%3$s</option>',
										esc_attr( $opt_val ),
										selected( $condition->comparator === $opt_val, true, false ),
										esc_html( $opt_label )
									);
								}
								?>
							</select>

							<?php
							switch ( $condition_type ) {
								case 'text':
									?>

								<div class="meta-text-field-wrapper field-wrapper-styles meta-value-wrapper" style="display: none;" data-dependency_measure="core_query_string" data-dependency_comparator="key_value">
									<input type="text" name="personalizewp_form[conditions][meta_value][]" value="<?php echo esc_attr( $condition_meta_value ); ?>"
										class="text-field field-meta-value-text" placeholder="<?php esc_attr_e( '-- Enter key here --', 'personalizewp' ); ?>" autocomplete="off" />
								</div>

								<select name="personalizewp_form[conditions][value][]" class="conditions-value chosen-select" <?php echo ( $condition->comparator === 'any' ? esc_attr( ' multiple' ) : '' ); ?>>
									<?php
									foreach ( $condition_comparison_val_opts as $opt_val => $opt_label ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											esc_attr( $opt_val ),
											selected( $condition->value === $opt_val, true, false ),
											esc_html( $opt_label )
										);
									}
									?>
								</select>

								<div class="text-field-wrapper field-wrapper-styles">
									<input type="text" name="personalizewp_form[conditions][value][]" value="<?php echo esc_attr( $condition->value ); ?>"
										class="text-field field-value-text" placeholder="<?php esc_attr_e( '-- Enter value here --', 'personalizewp' ); ?>" autocomplete="off" />
								</div>
								<div class="datepicker-field-wrapper field-wrapper-styles">
									<input type="text" name="personalizewp_form[conditions][value][]" value=""
										class="datepicker-field field-value-datepicker" placeholder="<?php esc_attr_e( '-- Select a date --', 'personalizewp' ); ?>" autocomplete="off" />
								</div>
									<?php
									break;
								case 'datepicker':
									if ( ! empty( $condition->value ) ) :
										$formattedDate = gmdate( 'd/m/Y', strtotime( $condition->value ) );
									else :
										$formattedDate = '';
									endif;
									?>

									<select name="personalizewp_form[conditions][value][]" class="conditions-value chosen-select" <?php echo ( $condition->comparator === 'any' ? esc_attr( ' multiple' ) : '' ); ?>>
										<?php
										foreach ( $condition_comparison_val_opts as $opt_val => $opt_label ) {
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												esc_attr( $opt_val ),
												selected( $condition->value === $opt_val, true, false ),
												esc_html( $opt_label )
											);
										}
										?>
									</select>

									<div class="text-field-wrapper field-wrapper-styles">
										<input type="text" name="personalizewp_form[conditions][value][]" value=""
											class="text-field field-value-text" placeholder="<?php esc_attr_e( '-- Enter value here --', 'personalizewp' ); ?>" autocomplete="off" />
									</div>
									<div class="datepicker-field-wrapper field-wrapper-styles">
										<input type="text" name="personalizewp_form[conditions][value][]" value="<?php echo esc_attr( $formattedDate ); ?>"
											class="datepicker-field field-value-datepicker" placeholder="<?php esc_attr_e( '-- Select a date --', 'personalizewp' ); ?>" autocomplete="off" />
									</div>

									<?php
									break;
								default:
									?>

									<select name="personalizewp_form[conditions][value][]" class="conditions-value chosen-select" <?php echo ( $condition->comparator === 'any' ? esc_attr( ' multiple' ) : '' ); ?>>
										<?php
										foreach ( $condition_comparison_val_opts as $opt_val => $opt_label ) {
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												esc_attr( $opt_val ),
												selected( $condition->value == $opt_val, true, false ), // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Required to be loose comparison for time elapsed conditions.
												esc_html( $opt_label )
											);
										}
										?>
									</select>

									<div class="text-field-wrapper field-wrapper-styles">
										<input type="text" name="personalizewp_form[conditions][value][]" value=""
											class="text-field field-value-text" placeholder="<?php esc_attr_e( '-- Enter value here --', 'personalizewp' ); ?>" autocomplete="off" />
									</div>
									<div class="datepicker-field-wrapper field-wrapper-styles">
										<input type="text" name="personalizewp_form[conditions][value][]" value=""
											class="datepicker-field field-value-datepicker" placeholder="<?php esc_attr_e( '-- Select a date --', 'personalizewp' ); ?>" autocomplete="off" />
									</div>
									<?php
									break;
							}
							?>

							<input type="hidden" name="personalizewp_form[conditions][raw_value][]" value="<?php echo esc_attr( $condition->raw_value ); ?>" class="conditions-raw-value" />
							<input type="hidden" name="personalizewp_form[conditions][comparisonType][]" value="<?php echo esc_attr( $condition_type ); ?>"class="the-comparison-type" />

								<div class="button-conditions-wrapper">
									<button type="button" class="remove-condition"><img class="" alt="<?php esc_attr_e( 'Remove', 'personalizewp' ); ?>" src="<?php echo esc_url( plugins_url( '../../img/bin.svg', __FILE__ ) ); ?>"></button>
								</div>

						</div>
						<?php
						if ( $this->getError( "conditions[{$i}]" ) ) :
							?>
							<div class="form-control is-invalid" style="display: none;"></div>
							<div class="invalid-feedback">
								<span class="dashicons dashicons-warning"></span>
								<?php echo esc_html( implode( ', ', $this->getError( "conditions[{$i}]" ) ) ); ?>
							</div>
							<?php
						endif;
					endforeach;
				else :
					?>
					<div data-condition-count="0" data-condition="row" class="condition mb-3 mb-sm-3">
						<select name="personalizewp_form[conditions][measure][]" class="conditions-measure chosen-select">
							<?php
							foreach ( $all_grouped_conditions_options as $opt_val => $opt_label ) {
								if ( is_array( $opt_label ) ) {
									printf( '<optgroup label="%s">', esc_attr( $opt_val ) );
									foreach ( $opt_label as $sub_opt_val => $sub_opt_label ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											esc_attr( $sub_opt_val ),
											selected( null === $sub_opt_val, true, false ),
											esc_html( $sub_opt_label )
										);
									}
									echo '</optgroup>';
								} else {
									printf(
										'<option value="%1$s" %2$s>%3$s</option>',
										esc_attr( $opt_val ),
										selected( null === $opt_val, true, false ),
										esc_html( $opt_label )
									);
								}
							}
							?>
						</select>
						<div class="meta-text-field-wrapper field-wrapper-styles meta-value-wrapper" style="display: none;">
							<input type="text" name="personalizewp_form[conditions][meta_value][]" value=""
								class="text-field field-meta-value-text" placeholder="<?php esc_attr_e( '-- Enter cookie name here --', 'personalizewp' ); ?>" autocomplete="off" />
						</div>

						<select name="personalizewp_form[conditions][comparator][]" class="conditions-comparator chosen-select">
							<?php
							$options = [ '' => __( '-- Select a comparator --', 'personalizewp' ) ];
							foreach ( $options as $opt_val => $opt_label ) {
								printf(
									'<option value="%1$s">%2$s</option>',
									esc_attr( $opt_val ),
									esc_html( $opt_label )
								);
							}
							?>
						</select>

						<select name="personalizewp_form[conditions][value][]" class="conditions-value chosen-select field-value-select">
							<?php
							$options = [ '' => __( '-- Select a value --', 'personalizewp' ) ];
							foreach ( $options as $opt_val => $opt_label ) {
								printf(
									'<option value="%1$s" %2$s>%3$s</option>',
									esc_attr( $opt_val ),
									selected( null === $opt_val, true, false ),
									esc_html( $opt_label )
								);
							}
							?>
						</select>

						<div class="meta-text-field-wrapper field-wrapper-styles meta-value-wrapper" style="display: none;" data-dependency_measure="core_query_string" data-dependency_comparator="key_value">
							<input type="text" name="personalizewp_form[conditions][meta_value][]" value=""
								class="text-field field-meta-value-text" placeholder="<?php esc_attr_e( '-- Enter key here --', 'personalizewp' ); ?>" autocomplete="off" />
						</div>

						<div class="text-field-wrapper field-wrapper-styles" style="display: none;">
							<input type="text" name="personalizewp_form[conditions][value][]" value=""
								class="text-field field-value-text" placeholder="<?php esc_attr_e( '-- Enter value here --', 'personalizewp' ); ?>" autocomplete="off" />
						</div>

						<div class="datepicker-field-wrapper field-wrapper-styles" style="display: none;">
							<input type="text" name="personalizewp_form[conditions][value][]" value=""
								class="datepicker-field field-value-datepicker" placeholder="<?php esc_attr_e( '-- Select a date --', 'personalizewp' ); ?>" autocomplete="off" />
						</div>

						<input type="hidden" name="personalizewp_form[conditions][raw_value][]" value="" class="conditions-raw-value" />
						<input type="hidden" name="personalizewp_form[conditions][comparisonType][]" value="select" class="the-comparison-type" />

						<div class="button-conditions-wrapper">
							<button type="button" class="remove-condition"><img class="" alt="<?php esc_attr_e( 'Remove', 'personalizewp' ); ?>" src="<?php echo esc_url( plugins_url( '../../img/bin.svg', __FILE__ ) ); ?>"></button>
						</div>
					</div>
					<?php
					if ( $this->getError( 'conditions[0]' ) ) :
						?>
					<div class="form-control is-invalid" style="display: none;"></div>
					<div class="invalid-feedback"><?php echo esc_html( implode( ', ', $this->getError( 'conditions[0]' ) ) ); ?></div>
						<?php
					endif;
					?>

					<?php
				endif;
				?>

			</div>
			<div id="condition-template" hidden>
					<div data-condition="added" class="condition mb-3 mb-sm-3">

						<select name="personalizewp_form[conditions][measure][]" class="conditions-measure" disabled>
							<?php
							foreach ( $all_grouped_conditions_options as $opt_val => $opt_label ) {
								if ( is_array( $opt_label ) ) {
									printf( '<optgroup label="%s">', esc_attr( $opt_val ) );
									foreach ( $opt_label as $sub_opt_val => $sub_opt_label ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											esc_attr( $sub_opt_val ),
											selected( null === $sub_opt_val, true, false ),
											esc_html( $sub_opt_label )
										);
									}
									echo '</optgroup>';
								} else {
									printf(
										'<option value="%1$s" %2$s>%3$s</option>',
										esc_attr( $opt_val ),
										selected( null === $opt_val, true, false ),
										esc_html( $opt_label )
									);
								}
							}
							?>
						</select>

						<div class="meta-text-field-wrapper field-wrapper-styles meta-value-wrapper" style="display: none;">
							<input type="text" name="personalizewp_form[conditions][meta_value][]" placeholder="<?php esc_attr_e( '-- Enter cookie name here --', 'personalizewp' ); ?>"
								class="text-field field-meta-value-text" autocomplete="off" disabled />
						</div>

						<select name="personalizewp_form[conditions][comparator][]" class="conditions-comparator" disabled>
							<?php
							$options = [ '' => __( '-- Select a comparator --', 'personalizewp' ) ];
							foreach ( $options as $opt_val => $opt_label ) {
								printf(
									'<option value="%1$s">%2$s</option>',
									esc_attr( $opt_val ),
									esc_html( $opt_label )
								);
							}
							?>
						</select>

						<select name="personalizewp_form[conditions][value][]" class="conditions-value" disabled>
							<?php
							$options = [ '' => __( '-- Select a value --', 'personalizewp' ) ];
							foreach ( $options as $opt_val => $opt_label ) {
								printf(
									'<option value="%1$s" %2$s>%3$s</option>',
									esc_attr( $opt_val ),
									selected( null === $opt_val, true, false ),
									esc_html( $opt_label )
								);
							}
							?>
						</select>
						<div class="meta-text-field-wrapper field-wrapper-styles meta-value-wrapper" style="display: none;" data-dependency_measure="core_query_string" data-dependency_comparator="key_value">
							<input type="text" name="personalizewp_form[conditions][meta_value][]" placeholder="<?php esc_attr_e( '-- Enter key here --', 'personalizewp' ); ?>"
								class="text-field field-meta-value-text" autocomplete="off" disabled />
						</div>

						<div class="text-field-wrapper field-wrapper-styles" style="display: none;">
							<input type="text" name="personalizewp_form[conditions][value][]" placeholder="<?php esc_attr_e( '-- Enter value here --', 'personalizewp' ); ?>"
								class="text-field field-value-text" autocomplete="off" />
						</div>
						<div class="datepicker-field-wrapper field-wrapper-styles" style="display: none;">
							<input type="text" name="personalizewp_form[conditions][value][]" value=""
								class="datepicker-field field-value-datepicker" placeholder="<?php esc_attr_e( '-- Select a date --', 'personalizewp' ); ?>" autocomplete="off" />
						</div>

						<input type="hidden" name="personalizewp_form[conditions][raw_value][]" value="" class="conditions-raw-value" disabled />
						<input type="hidden" name="personalizewp_form[conditions][comparisonType][]" value="" class="the-comparison-type" disabled />

						<div class="button-conditions-wrapper">
							<button type="button" class="remove-condition"><img class="" alt="<?php esc_attr_e( 'Remove', 'personalizewp' ); ?>" src="<?php echo esc_url( plugins_url( '../../img/bin.svg', __FILE__ ) ); ?>"></button>
						</div>
					</div>
				</div>

				<script type="text/javascript">
					var wpDxpConditions = <?php echo wp_json_encode( Rules_Conditions::to_object() ); ?>;
				</script>
		</div>
	</div>
</div>

	<div class="mb-3">
		<button type="button" class="add-condition">&plus; <?php esc_html_e( 'Add', 'personalizewp' ); ?></button>
	</div>

<div class="mt-4" id="form-actions">
	<?php
	if ( 0 !== (int) $rule->id ) :
		?>
		<button type="submit" class="primary btn"><?php esc_html_e( 'Save Changes', 'personalizewp' ); ?></button>
		<button type="reset" class="secondary btn"><?php esc_html_e( 'Reset', 'personalizewp' ); ?></button>
		<?php
	else :
		?>
		<button type="submit" class="primary btn"><?php esc_html_e( 'Create rule', 'personalizewp' ); ?></button>
		<a href="<?php echo esc_url( PERSONALIZEWP_ADMIN_DASHBOARD_INDEX_URL ); ?>" class="secondary btn"><?php esc_html_e( 'Cancel', 'personalizewp' ); ?></a>
		<?php
	endif;
	?>
</div>


<script type="text/javascript">
(function( $ ) {
	'use strict';

	$( ".the-comparison-type" ).each(function( i ) {
		showCorrectComparisonTypeField(this);
	});

	$( ".conditions-measure" ).each(function( i ) {
		showMeasureKeyField(this);
	});

	$(window).load(function() {
		const ruleForm  = $('#wp-dxp-form');
		const formActions = $('#wp-dxp-form #form-actions');
		const consContainer = document.querySelector('#conditions-container');
		formActions.hide();
		ruleForm.on( 'change', function() {
			formActions.show();
			var count = consContainer.querySelectorAll('.condition:not([data-condition=deleted]').length;
			consContainer.setAttribute( 'data-condition-count', count );
		} );
		ruleForm.on( 'input', function() {
			formActions.show();
		} );
		ruleForm.on( 'submit', function() {
			// Remove all 'deleted' inputs so not to affect server processing.
			var deletedCons = document.querySelectorAll( 'div[data-condition="deleted"]' );
			deletedCons.forEach(row => {
				row.parentNode.removeChild(row);
			});
		} );
		ruleForm.on( 'reset', function() {
			// Revert all 'deleted' rows.
			var deletedCons = document.querySelectorAll( 'div[data-condition="deleted"]' );
			deletedCons.forEach(row => {
				row.dataset.condition = row.dataset.prevCondition;
				row.hidden = false;
			});
			// Remove all 'added' rows.
			var addedCons = document.querySelectorAll( 'div[data-condition="added"]' );
			addedCons.forEach(row => {
				row.parentNode.removeChild(row);
			});
			var count = consContainer.querySelectorAll('.condition:not([data-condition=deleted]').length;
			consContainer.setAttribute( 'data-condition-count', count );
			formActions.hide();
		} );
	} );

	if (typeof wpDxpConditions !== 'undefined') {
		var $conditionTemplate = $('#condition-template .condition');
		var $comparatorEl;
		var $valueEl;

		$(document).on('change', '.conditions-measure', function() {
			personalizewp_measure_set_related_fields(this);
		});

		$(document).on('change', '.conditions-comparator', function() {
			personalizewp_set_value_field_type(this);
			personalizewp_comparator_set_related_fields(this);
		});

		$(document).on('change', '.conditions-value', function() {
			personalizewp_set_raw_value_field_value(this);
		});

		$(document).on('click', '.remove-condition', function(e) {
			var parent = e.target.closest("[data-condition]");
			// Mark it was 'deleted'.
			parent.dataset.prevCondition = parent.dataset.condition;
			parent.dataset.condition = 'deleted';
			parent.hidden = true;
			$('#wp-dxp-form').trigger('change'); // Trigger jquery compat event
		});

		$(document).on('keyup blur', '.field-value-text', function() {
			var $rawValuesEl = $(this).parents('.condition').find('.conditions-raw-value');

			$rawValuesEl.val($(this).val());
		});

		$(document).on('focus',".datepicker-field", function() {
			$(this).datepicker({
				dateFormat: 'dd/mm/yy',
				altField: $(this).parents('.condition').find('.conditions-raw-value'),
				altFormat: 'yy-mm-dd'
			});
		});

		$(document).on('click', '.add-condition', function(e) {
			var $template = $conditionTemplate.clone();

			$template.children('select, input').prop("disabled", false);

			$template.find('.field-meta-value-text').prop("disabled", false);

			var value = $template.children('.conditions-value').first().val();
			$template.children('.conditions-raw-value').val(value);

			$('#conditions-container').append($template);
		});
	}

	function personalizewp_set_value_field_type(el, measure) {

		var $parent = $(el).parent();
		var comparator = $(el).val();
		var $textInput = $parent.find('.field-value-text');

		$textInput.prop("disabled", false);

		if (typeof wpDxpConditions[measure] !== 'undefined') {

			if (wpDxpConditions[measure].comparisonType === 'select') {

				var $valueEl = $parent.children('.conditions-value');
				$valueEl.prop("multiple", (comparator === 'any') ? "multiple" : "");
			}
		} else {
			if ( 'any_value' === comparator || 'no_value' === comparator ) {
				$textInput.prop("disabled", true);
			}
		}
	}

	function personalizewp_measure_set_related_fields(el) {

		var $parent = $(el).parent();
		var measure = $(el).val();
		var $rawValuesEl = $parent.find('.conditions-raw-value');

		var $comparatorEl = $parent.children('.conditions-comparator');
		var $valueEl = $parent.children('.conditions-value');
		var $comparisonTypeEl = $parent.find('.the-comparison-type');

		var $selectFieldContainer = $parent.find('select:last');
		var $textFieldContainer = $parent.find('.text-field-wrapper');
		var $datePickerFieldContainer = $parent.find('.datepicker-field-wrapper');
		var $metaTextFieldContainer = $parent.find('.meta-text-field-wrapper');

		$comparisonTypeEl.val(wpDxpConditions[measure].comparisonType);

		// reset input values
		$textFieldContainer.children('.field-value-text').val('');
		$datePickerFieldContainer.children('.field-value-datepicker').val('');
		$metaTextFieldContainer.children('.field-meta-value-text').val('');

		$comparatorEl.find('option').not(':first').remove();
		$valueEl.find('option').not(':first').remove();

		if (typeof wpDxpConditions[measure] !== 'undefined') {
			$.each(wpDxpConditions[measure].comparators, function(key,value) {
				$comparatorEl.append($("<option></option>")
					.attr("value", key).text(value));
			});

			$.each(wpDxpConditions[measure].comparisonValues, function(key,value) {
				$valueEl.append($("<option></option>")
					.attr("value", key).text(value));
			});
		}

		personalizewp_set_value_field_type(el, measure);
		personalizewp_set_raw_value_field_value(el);

		// show/hide field types dependant on the measure
		showMeasureKeyField(el);
		showCorrectComparisonTypeField(el);
	}

	function showMeasureKeyField(el) {
		var $parent = $(el).parent();
		var measure = $(el).val();
		var comparator = $parent.children('.conditions-comparator').val();
		var $textInput = $parent.find('.field-value-text');
		var $metaTextFieldContainer = $parent.find('.meta-text-field-wrapper');

		if (measure === 'core_cookie') {
			$metaTextFieldContainer.show();
		} else {
			$metaTextFieldContainer.hide();
		}

		if ( 'any_value' === comparator || 'no_value' === comparator ) {
			$textInput.prop("disabled", true);
		}

		personalizewp_comparator_set_related_fields($parent.children('.conditions-comparator'));
	}

	function showCorrectComparisonTypeField(el) {
		var $parent = $(el).parent();

		var $comparisonTypeValue = $parent.find('.the-comparison-type').val();

		var $selectFieldContainer = $parent.find('select:last');
		var $textFieldContainer = $parent.find('.text-field-wrapper');
		var $datePickerFieldContainer = $parent.find('.datepicker-field-wrapper');

		if (screen.width < 600) {
			$selectFieldContainer = $parent.find('.conditions-value');
		}

		switch($comparisonTypeValue) {
			case 'text':
				$selectFieldContainer.hide();
				$textFieldContainer.show();
				$datePickerFieldContainer.hide();
			break;
			case 'datepicker':
				$selectFieldContainer.hide();
				$textFieldContainer.hide();
				$datePickerFieldContainer.show();
			break;
			default:
				$selectFieldContainer.show();
				$textFieldContainer.hide();
				$datePickerFieldContainer.hide();
		}
	}

	function personalizewp_set_raw_value_field_value(el) {
		var $parent = $(el).parent();

		var $valueEl = $parent.children('.conditions-value');
		var $rawValuesEl = $parent.children('.conditions-raw-value').first();
		var value = $valueEl.val();

		if (Array.isArray(value)) {
			$rawValuesEl.val(JSON.stringify(value));
		} else {
			$rawValuesEl.val(value);
		}
	}

	/**
	 * Displays related fields based on comparator value
	 *
	 * @param $comparator comparator element
	 */
	function personalizewp_comparator_set_related_fields($comparator) {
		let $parent = $($comparator).parents('.condition');
		let measure = $parent.find('.conditions-measure').val();
		let comparator = $($comparator).val();

		$parent.find('[data-dependency_measure][data-dependency_comparator]').hide()
			.find('[name]').prop('disabled', 'disabled');

		let dependencyFields = $parent.find('[data-dependency_measure="'+ measure +'"][data-dependency_comparator="'+ comparator +'"]');
		dependencyFields.each(function() {
			let $field = $(this).find('[name]');

			$parent.find('[name="'+ $field.attr('name') +'"]').prop('disabled', true);
			$field.prop('disabled', false);

			$(this).show();
		});
	}
	$(window).on('load', function() {
		$('.conditions-comparator').each(function() {
			personalizewp_comparator_set_related_fields($(this));
		});
	});

})( jQuery );

</script>
