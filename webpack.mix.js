const mix = require('laravel-mix');
const min = Mix.inProduction() ? '.min' : '';

require('laravel-mix-storepress');

mix.js(`assets/src/js/admin.js`, `assets/public/js/admin${min}.js`);

mix.sass(`assets/src/scss/admin.scss`, `assets/public/css/admin${min}.css`);

// OS Notification
mix.notification('WPWing PDF Invoices Packing Slips for WooCommerce');  // Example: Mix.paths.root('images/icon.png')

// File Banner
mix.banner("WPWing PDF Invoices Packing Slips for WooCommerce v1.0.0 \n\nAuthor: WP Wing ( wpwing.dev@gmail.com ) \nDate: " + new Date().toLocaleString() + "\nReleased under the GPLv3 license.");

// WP Translation
mix.translation('WPWing PDF Invoices Packing Slips for WooCommerce', 'wpwing-wc-pdf-invoice');

// Some WP Tasks
mix.wp();

// Create Package
// mix.package(`assets
// includes
// languages
// product-bundles-for-woocommerce.php
// README.txt
// templates`); // Will run on: npm run package