#!/bin/sh
set -e

WP_PATH=/var/www/html

echo "Waiting for wp-config.php..."
until [ -f "$WP_PATH/wp-config.php" ]; do
    sleep 2
done

if wp --path="$WP_PATH" core is-installed --allow-root 2>/dev/null; then
    echo "Already installed, skipping setup."
else
    echo "Installing WordPress..."
    wp --path="$WP_PATH" core install \
        --url="https://pdf-invoice.local" \
        --title="PDF Invoice Dev" \
        --admin_user=admin \
        --admin_password=password \
        --admin_email=dev@example.com \
        --skip-email \
        --allow-root

    echo "Installing WooCommerce..."
    wp --path="$WP_PATH" plugin install woocommerce --activate --allow-root
    wp --path="$WP_PATH" option update woocommerce_coming_soon no --allow-root

    echo "Importing WooCommerce sample data..."
    wp --path="$WP_PATH" plugin install wordpress-importer --activate --allow-root
    wp --path="$WP_PATH" import \
        "$WP_PATH/wp-content/plugins/woocommerce/sample-data/sample_products.xml" \
        --authors=create \
        --allow-root

    echo "Activating plugin..."
    wp --path="$WP_PATH" plugin activate wpwing-pdf-invoice-packing-slip-for-woocommerce --allow-root
fi

echo "Done. Visit https://pdf-invoice.local/wp-admin (admin / password)"
