<?php
/**
 * Seed a handful of dummy WooCommerce orders against the imported sample
 * products, and generate a few real invoice/packing slip/delivery note
 * PDFs, so the plugin has something to test immediately after a fresh
 * `make dev` / `make env-reset`.
 *
 * Run once from setup.sh, only on first install of a fresh volume.
 */

defined( 'ABSPATH' ) || exit;

$products = wc_get_products(
	array(
		'limit'   => 5,
		'status'  => 'publish',
		'orderby' => 'ID',
		'order'   => 'ASC',
	)
);

// Fall back to a couple of simple products if the sample data import
// produced nothing (e.g. a flaky network on first run).
if ( empty( $products ) ) {
	foreach ( array( array( 'Sample Hoodie', 45.00 ), array( 'Sample Mug', 12.50 ) ) as $spec ) {
		list( $name, $price ) = $spec;
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( (string) $price );
		$product->set_status( 'publish' );
		$product->save();
		$products[] = $product;
	}
}

$customers = array(
	array( 'John', 'Doe', 'john.doe@example.com', '+1 555 0100' ),
	array( 'Jane', 'Smith', 'jane.smith@example.com', '+1 555 0101' ),
	array( 'Karim', 'Rahman', 'karim.rahman@example.com', '+880 1711 000000' ),
);

$orders_spec = array(
	array(
		'status' => 'processing',
		'items'  => 2,
	),
	array(
		'status' => 'processing',
		'items'  => 1,
	),
	array(
		'status' => 'completed',
		'items'  => 3,
	),
	array(
		'status' => 'completed',
		'items'  => 1,
	),
	array(
		'status' => 'on-hold',
		'items'  => 2,
	),
	array(
		'status' => 'pending',
		'items'  => 1,
	),
);

$created = array();

foreach ( $orders_spec as $i => $spec ) {
	list( $first, $last, $email, $phone ) = $customers[ $i % count( $customers ) ];

	$order = wc_create_order();

	$address = array(
		'first_name' => $first,
		'last_name'  => $last,
		'email'      => $email,
		'phone'      => $phone,
		'address_1'  => ( 100 + $i ) . ' Example Street',
		'city'       => 'Dhaka',
		'postcode'   => '1207',
		'country'    => 'BD',
	);
	$order->set_address( $address, 'billing' );
	$order->set_address( $address, 'shipping' );

	$pool = $products;
	shuffle( $pool );
	foreach ( array_slice( $pool, 0, max( 1, $spec['items'] ) ) as $product ) {
		$order->add_product( $product, wp_rand( 1, 3 ) );
	}

	$shipping = new WC_Order_Item_Shipping();
	$shipping->set_method_title( 'Flat rate' );
	$shipping->set_total( '5.00' );
	$order->add_item( $shipping );

	$order->set_payment_method_title( 'Cash on delivery' );
	$order->set_date_created( gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $i + 1 ) . ' days' ) ) );
	$order->calculate_totals();
	$order->update_status( $spec['status'], 'Seeded dummy order for local dev.' );
	$order->save();

	$created[] = array(
		'id'     => $order->get_id(),
		'status' => $spec['status'],
	);
}

// Generate real PDFs for a few orders so they're visible immediately,
// without having to click anything in the admin first.
global $wpwing_wcpdf;
if ( $wpwing_wcpdf && ! empty( $created ) ) {
	foreach ( array_slice( $created, 0, 3 ) as $entry ) {
		$wpwing_wcpdf->create_document( $entry['id'], 'invoice' );
		$wpwing_wcpdf->create_document( $entry['id'], 'packing' );
	}
	$wpwing_wcpdf->create_document( $created[0]['id'], 'delivery' );
}

echo 'Seeded ' . count( $created ) . " dummy WooCommerce orders.\n";
