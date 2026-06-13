<?php
/**
 * Proforma invoice footer template - Modern theme.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

?>
<?php if ( isset( $notes ) ) : ?>
	<div class="notes">
		<span class="notes-title"><?php esc_html_e( 'Notes', 'wpwing-pdf-invoice-pro' ); ?></span>
		<span><?php echo esc_html( nl2br( $notes ) ); ?></span>
	</div>
<?php endif; ?>

<?php if ( isset( $footer ) ) : ?>
	<footer>
		<span><?php echo esc_html( nl2br( $footer ) ); ?></span>
	</footer>
<?php endif; ?>
