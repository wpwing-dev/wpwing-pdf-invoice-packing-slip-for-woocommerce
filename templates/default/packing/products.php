<?php global $wpwing_wcpi_document; ?>

<table class="invoice-details">
	<thead>
		<tr>
			<th class="column-product"><?php _e( 'Product', 'wpwing-wc-pdf-invoice' ); ?></th>
			<th class="column-quantity"><?php _e( 'Quantity', 'wpwing-wc-pdf-invoice' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php

	$order_items = $wpwing_wcpi_document->order->get_items();
	foreach ( $order_items as $item_id => $item ) {
		?>

		<tr>
			<td class="column-product"><?php echo esc_html( $item['name'] ); ?></td>
			<td class="column-quantity"><?php echo ( isset( $item['qty'] ) ) ? esc_html( $item['qty'] ) : ''; ?></td>
		</tr>

	<?php }; ?>

	</tbody>
</table>