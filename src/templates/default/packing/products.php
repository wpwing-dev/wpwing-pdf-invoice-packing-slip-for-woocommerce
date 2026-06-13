<?php
/**
 * Packing slip products table template.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

global $wpwing_wcpdf_document;
?>

<table class="invoice-details">
	<thead>
		<tr>
			<th class="column-product"><?php esc_html_e( 'Product', 'wpwing-wcpdf' ); ?></th>
			<th class="column-quantity"><?php esc_html_e( 'Quantity', 'wpwing-wcpdf' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php

	$order_items = $wpwing_wcpdf_document->order->get_items();
	foreach ( $order_items as $item_id => $item ) {
		?>

		<tr>
			<td class="column-product"><?php echo esc_html( $item['name'] ); ?></td>
			<td class="column-quantity"><?php echo ( isset( $item['qty'] ) ) ? esc_html( $item['qty'] ) : ''; ?></td>
		</tr>

	<?php } ?>

	</tbody>
</table>