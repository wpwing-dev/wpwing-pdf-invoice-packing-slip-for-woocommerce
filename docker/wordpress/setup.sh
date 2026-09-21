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

    echo "Seeding dummy WooCommerce orders..."
    wp --path="$WP_PATH" eval-file /docker/wordpress/seed-orders.php --allow-root
fi

# This script runs as root, so everything it creates under wp-content (uploads,
# the upgrade/ staging dir every `wp plugin install` uses, and the installed
# plugins themselves) ends up root-owned, and the web server can't write PDFs,
# font caches, or install/update plugins through wp-admin afterwards. Use
# numeric 33 (www-data in the Debian wordpress image - this Alpine CLI image
# maps www-data to 82). Everything under wp-content is chowned EXCEPT this
# plugin's own directory, which is a bind mount of the host working tree.
echo "Fixing wp-content ownership..."
mkdir -p "$WP_PATH/wp-content/uploads" "$WP_PATH/wp-content/upgrade"
find "$WP_PATH/wp-content" -mindepth 1 -maxdepth 1 -not -name plugins -exec chown -R 33:33 {} +
find "$WP_PATH/wp-content/plugins" -mindepth 1 -maxdepth 1 \
    -not -name wpwing-pdf-invoice-packing-slip-for-woocommerce -exec chown -R 33:33 {} +

echo "Done. Visit https://pdf-invoice.local/wp-admin (admin / password)"
