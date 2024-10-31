<?php
defined( 'WPINC' ) || exit;

if ( $this->getError( 'form' ) ) : ?>
<div class="alert alert-danger" role="alert">
	<?php
	echo implode( '<br>', array_map( 'esc_html', $this->getError( 'form' ) ) );
	?>
</div>
	<?php
endif;
?>

<input type="hidden" name="personalizewp_form[id]" value="<?php echo esc_attr( $category->id ); ?>" />

<label for="personalizewp_form[name]"><?php esc_html_e( 'Category Name', 'personalizewp' ); ?></label>
<input type="text" required autofocus id="personalizewp_form[name]" name="personalizewp_form[name]" value="<?php echo esc_attr( $category->name ); ?>"
	class="form-control <?php echo ( $this->getError( 'name' ) ? esc_attr( 'is-invalid' ) : '' ); ?>" />

<?php
if ( $this->getError( 'name' ) ) :
	?>
		<div class="invalid-feedback"><?php echo esc_html( implode( ', ', $this->getError( 'name' ) ) ); ?></div>
		<?php
	endif;
?>


<div class="actions">
	<?php
	if ( $category->can_edit ) :
		?>
		<button type="submit" class="primary btn"><?php esc_html_e( 'Save Category', 'personalizewp' ); ?></button>
		<?php
	endif;

	if ( ! empty( $modal_mode ) ) :
		?>
		<button type="button" formnovalidate value="close" formmethod="dialog" class="secondary btn"><?php esc_html_e( 'Cancel', 'personalizewp' ); ?></button>
		<?php
	else :
		?>
		<a href="<?php echo esc_url( PERSONALIZEWP_ADMIN_CATEGORIES_INDEX_URL ); ?>" class="secondary btn"><?php esc_html_e( 'Cancel', 'personalizewp' ); ?></a>
		<?php
	endif;
	?>
</div>
