<?php
/**
 * Shipping label footer template - Modern theme.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

?>
<?php if ( isset( $notes ) ) : ?>
	<div class="notes">
		<span class="notes-title"><?php esc_html_e( 'Notes', 'wpwing-wcpdf' ); ?></span>
		<span><?php echo nl2br( esc_html( $notes ) ); ?></span>
	</div>
<?php endif; ?>

<?php if ( isset( $footer ) ) : ?>
	<footer>
		<span><?php echo nl2br( esc_html( $footer ) ); ?></span>
	</footer>
<?php endif; ?>
