<?php
/**
 * Delivery note products table template - Modern theme.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

global $wpwing_wcpdf_document;
$show_thumbnail  = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_product_thumbnail' );
$thumb_dimension = (int) ( $wpwing_wcpdf_document->settings->get_option( 'product_thumbnail_size' ) ? $wpwing_wcpdf_document->settings->get_option( 'product_thumbnail_size' ) : 32 );
$show_sku        = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_product_sku' );
$show_weight     = (bool) $wpwing_wcpdf_document->settings->get_option( 'show_product_weight' );
$weight_unit     = get_option( 'woocommerce_weight_unit', 'kg' );
?>

<table class="invoice-details">
	<thead>
		<tr>
			<?php if ( $show_thumbnail ) : ?>
				<th class="column-thumbnail"><?php esc_html_e( 'Image', 'wpwing-wcpdf' ); ?></th>
			<?php endif; ?>
			<th class="column-product"><?php esc_html_e( 'Product', 'wpwing-wcpdf' ); ?></th>
			<?php if ( $show_sku ) : ?>
				<th class="column-sku"><?php esc_html_e( 'SKU', 'wpwing-wcpdf' ); ?></th>
			<?php endif; ?>
			<?php if ( $show_weight ) : ?>
				<th class="column-weight"><?php esc_html_e( 'Weight', 'wpwing-wcpdf' ); ?></th>
			<?php endif; ?>
			<th class="column-quantity"><?php esc_html_e( 'Quantity', 'wpwing-wcpdf' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php

	$order_items = $wpwing_wcpdf_document->order->get_items();
	foreach ( $order_items as $item_id => $item ) {
		$product = $item->get_product();
		$sku     = '';
		$weight  = '';
		$thumb   = '';
		if ( $product ) {
			if ( $show_sku ) {
				$raw_sku = $product->get_sku();
				$sku     = $raw_sku ? $raw_sku : '-';
			}
			if ( $show_weight ) {
				$raw_weight = $product->get_weight();
				$weight     = ( '' !== $raw_weight && false !== $raw_weight ) ? $raw_weight . ' ' . $weight_unit : '-';
			}
			if ( $show_thumbnail ) {
				$thumb = $wpwing_wcpdf_document->get_product_thumbnail_data_uri( $product, $thumb_dimension );
			}
		}
		?>

		<tr>
			<?php if ( $show_thumbnail ) : ?>
				<td class="column-thumbnail">
					<?php if ( $thumb ) : ?>
						<img src="<?php echo esc_attr( $thumb ); ?>" width="<?php echo esc_attr( $thumb_dimension ); ?>" height="<?php echo esc_attr( $thumb_dimension ); ?>" alt="" />
					<?php endif; ?>
				</td>
			<?php endif; ?>
			<td class="column-product"><?php echo esc_html( $item['name'] ); ?></td>
			<?php if ( $show_sku ) : ?>
				<td class="column-sku"><?php echo esc_html( $sku ); ?></td>
			<?php endif; ?>
			<?php if ( $show_weight ) : ?>
				<td class="column-weight"><?php echo esc_html( $weight ); ?></td>
			<?php endif; ?>
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
