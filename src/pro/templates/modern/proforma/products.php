<?php
/**
 * Proforma invoice products table template - Modern theme.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

global $wpwing_wcpdf_document;
$show_sku = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_product_sku' );
?>

<table class="invoice-details">
	<thead>
	<tr>
		<th class="column-product"><?php esc_html_e( 'Product', 'wpwing-pdf-invoice-pro' ); ?></th>
		<?php if ( $show_sku ) : ?>
			<th class="column-sku"><?php esc_html_e( 'SKU', 'wpwing-pdf-invoice-pro' ); ?></th>
		<?php endif; ?>
		<th class="column-quantity"><?php esc_html_e( 'Qty', 'wpwing-pdf-invoice-pro' ); ?></th>
		<th class="column-price"><?php esc_html_e( 'Price', 'wpwing-pdf-invoice-pro' ); ?></th>
		<th class="column-total"><?php esc_html_e( 'Line total', 'wpwing-pdf-invoice-pro' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$order_items = $wpwing_wcpdf_document->order->get_items();
	foreach ( $order_items as $item_id => $item ) {
		if ( isset( $item['qty'] ) && $item['qty'] > 0 ) {
			$price_per_unit = $item['line_subtotal'] / $item['qty'];
		} else {
			$price_per_unit = 0;
		}
		$product = $item->get_product();
		$sku     = ( $show_sku && $product ) ? $product->get_sku() : '';
		?>
		<tr>
			<td class="column-product"><?php echo esc_html( $item['name'] ); ?></td>
			<?php if ( $show_sku ) : ?>
				<td class="column-sku"><?php echo esc_html( $sku ); ?></td>
			<?php endif; ?>
			<td class="column-quantity"><?php echo isset( $item['qty'] ) ? esc_html( $item['qty'] ) : ''; ?></td>
			<td class="column-price"><?php echo wp_kses_post( wc_price( $price_per_unit ) ); ?></td>
			<td class="column-total"><?php echo wp_kses_post( wc_price( $item['line_subtotal'] ) ); ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>

<table>
	<tr>
		<td class="column1"></td>
		<td class="column2">
			<table class="invoice-totals">
				<tr class="invoice-details-subtotal">
					<td class="column-product"><?php esc_html_e( 'Subtotal', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="column-total"><?php echo wp_kses_post( wc_price( $wpwing_wcpdf_document->order->get_subtotal() ) ); ?></td>
				</tr>
				<?php if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) : ?>
					<?php foreach ( $wpwing_wcpdf_document->order->get_tax_totals() as $code => $tax_totals_item ) : ?>
						<tr class="invoice-details-vat">
							<td class="column-product"><?php echo esc_html( $tax_totals_item->label ); ?>:</td>
							<td class="column-total"><?php echo esc_html( $tax_totals_item->formatted_amount ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				<tr class="invoice-details-total">
					<td class="column-product"><?php esc_html_e( 'Total', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="column-total"><?php echo wp_kses_post( wc_price( $wpwing_wcpdf_document->order->get_total() ) ); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
