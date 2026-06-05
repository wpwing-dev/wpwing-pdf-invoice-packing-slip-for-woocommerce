<?php
global $wpwing_wcpdf_document;
$show_sku           = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_product_sku' );
$show_tax_breakdown = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_tax_breakdown' );
$show_customer_note = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_customer_note' );
?>

<table class="invoice-details">
	<thead>
	<tr>
		<th class="column-product"><?php esc_html_e( 'Product', 'wpwing-wcpdf' ); ?></th>
		<?php if ( $show_sku ) : ?>
			<th class="column-sku"><?php esc_html_e( 'SKU', 'wpwing-wcpdf' ); ?></th>
		<?php endif; ?>
		<th class="column-quantity"><?php esc_html_e( 'Qty', 'wpwing-wcpdf' ); ?></th>
		<th class="column-price"><?php esc_html_e( 'Price', 'wpwing-wcpdf' ); ?></th>
		<th class="column-total"><?php esc_html_e( 'Line total', 'wpwing-wcpdf' ); ?></th>
		<th class="column-tax"><?php esc_html_e( 'Tax', 'wpwing-wcpdf' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php

	$order_items = $wpwing_wcpdf_document->order->get_items();
	foreach ( $order_items as $item_id => $item ) {
		if ( isset( $item['qty'] ) ) {
			$price_per_unit      = $item['line_subtotal'] / $item['qty'];
			$price_per_unit_sale = $item['line_total'] / $item['qty'];
			$discount            = $price_per_unit - $price_per_unit_sale;
		}
		$tax     = $item['line_tax'];
		$product = $item->get_product();
		$sku     = ( $show_sku && $product ) ? $product->get_sku() : '';

		?>

		<tr>
			<td class="column-product"><?php echo esc_html( $item['name'] ); ?></td>
			<?php if ( $show_sku ) : ?>
				<td class="column-sku"><?php echo esc_html( $sku ); ?></td>
			<?php endif; ?>
			<td class="column-quantity"><?php echo isset( $item['qty'] ) ? esc_html( $item['qty'] ) : ''; ?></td>
			<td class="column-price"><?php echo wc_price( $price_per_unit ); ?></td>
			<td class="column-total"><?php echo wc_price( $item['line_subtotal'] ); ?></td>
			<td class="column-tax"><?php echo wc_price( $tax ); ?></td>
		</tr>

	<?php };

	$order_shipping = $wpwing_wcpdf_document->order->get_items( 'shipping' );
	$total_shipping = 0.00;
	$total_shipping_tax = 0.00;

	foreach ( $order_shipping as $item_id => $item ) {
		if ( isset( $item['cost'] ) ) {
			$total_shipping += $item['cost'];
		}

		?>

		<tr>
			<td class="column-product">
				<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Shipping', 'wpwing-wcpdf' ); ?>
			</td>
			<?php if ( $show_sku ) : ?>
				<td class="column-sku"></td>
			<?php endif; ?>
			<td class="column-quantity"></td>
			<td class="column-price"></td>
			<td class="column-total">
				<?php echo ( isset( $item['cost'] ) ) ? wc_price( wc_round_tax_total( $item['cost'] ) ) : ''; ?>
			</td>
			<td class="column-tax">
				<?php
				$taxes = 0;
				$taxes_list = maybe_unserialize( $item['taxes'] );
				$taxes_list = isset( $taxes_list['total'] ) ? $taxes_list['total'] : $taxes_list;

				foreach ( $taxes_list as $tax_id => $amount ) {
					if ( 'total' != $tax_id ) {
						$taxes += (int)$amount;
					}
				}
				$total_shipping_tax += $taxes;
				echo wc_price( wc_round_tax_total( $taxes ) );
				?>
			</td>
		</tr>
		<?php
	};

	$order_fees = $wpwing_wcpdf_document->order->get_items( 'fee' );
	$total_fee = 0.00;
	$total_fee_tax = 0.00;

	foreach ( $order_fees as $item_id => $item ) {
		if ( isset( $item['line_total'] ) ) {
			$total_fee += $item['line_total'];
		}
		if ( isset( $item['line_tax'] ) ) {
			$total_fee_tax += $item['line_tax'];
		}
		?>

		<tr>
			<td class="column-product">
				<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Fee', 'wpwing-wcpdf' ); ?>
			</td>
			<?php if ( $show_sku ) : ?>
				<td class="column-sku"></td>
			<?php endif; ?>
			<td class="column-quantity"></td>
			<td class="column-price"></td>
			<td class="column-total">
				<?php echo ( isset( $item['line_total'] ) ) ? wc_price( wc_round_tax_total( $item['line_total'] ) ) : ''; ?>
			</td>
			<td class="column-tax">
				<?php echo ( isset( $item['line_tax'] ) ) ? wc_price( $item['line_tax'] ) : ''; ?>
			</td>
		</tr>
		<?php
	};
	?>

	</tbody>
</table>

<table>
	<tr>
		<td class="column1">

		</td>
		<td class="column2">
			<table class="invoice-totals">
				<tr class="invoice-details-subtotal">
					<td class="column-product"><?php _e( "Subtotal", 'wpwing-wcpdf' ); ?></td>
					<td class="column-total"><?php echo wc_price( $wpwing_wcpdf_document->order->get_subtotal() + $total_fee + $total_shipping ); ?></td>
				</tr>

				<tr>
					<td class="column-product"><?php _e( "Discount", 'wpwing-wcpdf' ); ?></td>
					<td class="column-total"><?php echo wc_price( $wpwing_wcpdf_document->order->get_total_discount() ); ?></td>
				</tr>

				<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>
					<?php if ( $show_tax_breakdown ) : ?>
						<?php foreach ( $wpwing_wcpdf_document->order->get_tax_totals() as $code => $tax ) : ?>
							<tr class="invoice-details-vat">
								<td class="column-product"><?php echo esc_html( $tax->label ); ?>:</td>
								<td class="column-total"><?php echo esc_html( $tax->formatted_amount ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr class="invoice-details-vat">
							<td class="column-product"><?php esc_html_e( 'Tax', 'wpwing-wcpdf' ); ?>:</td>
							<td class="column-total"><?php echo wc_price( $wpwing_wcpdf_document->order->get_total_tax() ); ?></td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>

				<tr class="invoice-details-total">
					<td class="column-product"><?php _e( "Total", 'wpwing-wcpdf' ); ?></td>
					<td class="column-total"><?php echo wc_price( $wpwing_wcpdf_document->order->get_total() ); ?></td>
				</tr>
			</table>
		</td>
	</tr>

</table>

<?php
$customer_note = $show_customer_note ? $wpwing_wcpdf_document->order->get_customer_note() : '';
if ( $customer_note ) : ?>
<div class="customer-note">
	<strong><?php esc_html_e( 'Customer note:', 'wpwing-wcpdf' ); ?></strong>
	<?php echo wp_kses( nl2br( $customer_note ), array( 'br' => array() ) ); ?>
</div>
<?php endif; ?>
