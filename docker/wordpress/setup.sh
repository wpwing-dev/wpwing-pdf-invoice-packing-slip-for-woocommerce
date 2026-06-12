#!/bin/sh
set -e

echo "Waiting for WordPress to be ready..."
until wp core is-installed --allow-root 2>/dev/null; do
    sleep 3
done
echo "WordPress is ready."

wp core install \
    --url="http://pdf-invoice.local:1122" \
    --title="PDF Invoice Dev" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=dev@example.com \
    --skip-email \
    --allow-root 2>/dev/null || true

wp plugin activate wpwing-pdf-invoice-packing-slip-for-woocommerce --allow-root 2>/dev/null || true
wp plugin install woocommerce --activate --allow-root 2>/dev/null || true

echo "Setup complete. Visit http://pdf-invoice.local:1122/wp-admin (admin / admin)"
