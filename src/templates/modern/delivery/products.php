<?php
/**
 * Delivery note products table template - Modern theme.
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

<?php
$show_customer_note = (bool) $wpwing_wcpdf_document->settings->get_option( 'delivery_show_customer_note' );
$customer_note      = $show_customer_note ? $wpwing_wcpdf_document->order->get_customer_note() : '';
if ( $customer_note ) :
	?>
<div class="customer-note">
	<strong><?php esc_html_e( 'Customer note:', 'wpwing-wcpdf' ); ?></strong>
	<?php echo wp_kses( nl2br( $customer_note ), array( 'br' => array() ) ); ?>
</div>
<?php endif; ?>
