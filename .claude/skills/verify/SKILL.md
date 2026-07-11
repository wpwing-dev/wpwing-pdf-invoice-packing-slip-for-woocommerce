---
name: verify
description: Build, launch, and drive this WooCommerce plugin in the local docker stack to verify changes end-to-end.
---

# Verifying changes in this plugin

`src/` is bind-mounted as the live plugin - working-tree changes run immediately, no build step for PHP. Assets need `make assets` only if SCSS/JS changed.

## Launch

```bash
docker compose --profile e2e up -d --wait db wordpress
docker compose --profile e2e run --rm wpcli        # provision (idempotent)
docker compose --profile e2e up -d caddy
```

Site: https://pdf-invoice.local (hosts entry to 127.0.0.2 already present; caddy uses a self-signed cert, use `curl -k`). Admin: `admin` / `password`. WP_DEBUG log: `docker compose exec wordpress cat /var/www/html/wp-content/debug.log`.

## Drive

- wp-cli one-liners: `docker compose --profile e2e run --rm wpcli wp --path=/var/www/html --allow-root eval '...'` - use this to create orders (`wc_create_order`), generate documents (`$GLOBALS["wpwing_wcpdf"]->create_document($id, "invoice")`), create pages, read order meta.
- Frontend/admin as a user: curl with a cookie jar - GET wp-login.php first, then POST `log`/`pwd`/`testcookie=1`.
- HPOS toggle (test both order storage modes): `wp wc hpos sync` then `wp wc hpos enable` / `wp wc hpos disable`.

## Gotchas

- Host `php` is 7.4; the dev vendor/ needs PHP 8 - run tooling as `php8.2 ./vendor/bin/phpcs` etc. `php8.2` lacks mbstring, so PHPUnit must run via `docker compose --profile unit run --rm unit`.
- phpstan has ~123 baseline errors (no WooCommerce stubs installed); only worry about NEW errors beyond baseline.
- The dev site uses a block theme: the checkout page content (and shortcodes in it) does NOT render on the Thank You page - switch to a classic theme (`wp theme install storefront --activate`) to exercise classic checkout/thank-you page content, and switch back after.
- Restore any state you toggled (theme, HPOS, page content) - the same volume serves Playwright e2e runs.
