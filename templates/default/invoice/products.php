<?php global $wpwing_wcpi_document; ?>

<table class="invoice-details">
	<thead>
	<tr>
		<th class="column-product"><?php _e( 'Product', 'wpwing-wc-pdf-invoice' ); ?></th>
		<th class="column-quantity"><?php _e( 'Qty', 'wpwing-wc-pdf-invoice' ); ?></th>
		<th class="column-price"><?php _e( 'Price', 'wpwing-wc-pdf-invoice' ); ?></th>
		<th class="column-total"><?php _e( 'Line total', 'wpwing-wc-pdf-invoice' ); ?></th>
		<th class="column-tax"><?php _e( 'Tax', 'wpwing-wc-pdf-invoice' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php

	$order_items = $wpwing_wcpi_document->order->get_items();
	foreach ( $order_items as $item_id => $item ) {
		if ( isset( $item['qty'] ) ) {
			$price_per_unit = $item["line_subtotal"] / $item['qty'];
			$price_per_unit_sale = $item["line_total"] / $item['qty'];
			$discount = $price_per_unit - $price_per_unit_sale;
		}
		$tax = $item["line_tax"];

		?>

		<tr>
			<td class="column-product"><?php echo esc_html( $item['name'] ); ?></td>
			<td class="column-quantity"><?php echo ( isset( $item['qty'] ) ) ? esc_html( $item['qty'] ) : ''; ?></td>
			<td class="column-price"><?php echo wc_price( $price_per_unit ); ?></td>
			<td class="column-total"><?php echo wc_price( $item["line_subtotal"] ); ?></td>
			<td class="column-tax"><?php echo wc_price( $tax ); ?></td>
		</tr>

	<?php };

	$order_shipping = $wpwing_wcpi_document->order->get_items( 'shipping' );
	$total_shipping = 0.00;
	$total_shipping_tax = 0.00;

	foreach ( $order_shipping as $item_id => $item ) {
		if ( isset( $item['cost'] ) ) {
			$total_shipping += $item['cost'];
		}

		?>

		<tr>
			<td class="column-product">
				<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Shipping', 'wpwing-wc-pdf-invoice' ); ?>
			</td>

			<td class="column-quantity">
			</td>

			<td class="column-price">
			</td>

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

	$order_fees = $wpwing_wcpi_document->order->get_items( 'fee' );
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
				<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Fee', 'wpwing-wc-pdf-invoice' ); ?>
			</td>

			<td class="column-quantity">
			</td>

			<td class="column-price">
			</td>

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
					<td class="column-product"><?php _e( "Subtotal", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="column-total"><?php echo wc_price( $wpwing_wcpi_document->order->get_subtotal() + $total_fee + $total_shipping ); ?></td>
				</tr>

				<tr>
					<td class="column-product"><?php _e( "Discount", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="column-total"><?php echo wc_price( $wpwing_wcpi_document->order->get_total_discount() ); ?></td>
				</tr>

				<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>
					<?php foreach ( $wpwing_wcpi_document->order->get_tax_totals() as $code => $tax ) : ?>
						<tr class="invoice-details-vat">
							<td class="column-product"><?php echo esc_html( $tax->label ); ?>:</td>
							<td class="column-total"><?php echo esc_html( $tax->formatted_amount ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>

				<tr class="invoice-details-total">
					<td class="column-product"><?php _e( "Total", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="column-total"><?php echo wc_price( $wpwing_wcpi_document->order->get_total() ); ?></td>
				</tr>
			</table>
		</td>
	</tr>

</table>
