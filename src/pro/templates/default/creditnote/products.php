<?php
/**
 * Credit note products table template.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

global $wpwing_wcpdf_document;
$refund = $wpwing_wcpdf_document->refund;
?>

<table class="invoice-details">
	<thead>
	<tr>
		<th class="column-product"><?php esc_html_e( 'Product', 'wpwing-pdf-invoice-pro' ); ?></th>
		<th class="column-quantity"><?php esc_html_e( 'Qty', 'wpwing-pdf-invoice-pro' ); ?></th>
		<th class="column-total"><?php esc_html_e( 'Refunded', 'wpwing-pdf-invoice-pro' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php if ( $refund ) : ?>
		<?php foreach ( $refund->get_items() as $item ) : ?>
			<tr>
				<td class="column-product"><?php echo esc_html( $item->get_name() ); ?></td>
				<td class="column-quantity"><?php echo esc_html( abs( $item->get_quantity() ) ); ?></td>
				<td class="column-total"><?php echo wp_kses_post( wc_price( abs( $item->get_total() ) ) ); ?></td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>

<table>
	<tr>
		<td class="column1"></td>
		<td class="column2">
			<table class="invoice-totals">
				<tr class="invoice-details-total">
					<td class="column-product"><?php esc_html_e( 'Total Refunded', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="column-total"><?php echo wp_kses_post( wc_price( $refund ? abs( $refund->get_total() ) : 0 ) ); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
