# PDF Invoice and Packing Slip for WooCommerce

[![CI](https://github.com/wpwing-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce/actions/workflows/ci.yml/badge.svg)](https://github.com/wpwing-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce/actions/workflows/ci.yml)
[![WordPress](https://img.shields.io/badge/WordPress-4.8%2B-21759B.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-4.5%2B-96588A.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.1%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

Automatically generate, print, and attach professional PDF invoices and packing slips to WooCommerce emails. Clean, lightweight, and fast.

---

## Requirements

- PHP 7.1 or higher
- WordPress 4.8 or higher
- WooCommerce 4.5 or higher
- Docker (for the local dev environment)
- Composer 2
- Node.js 20 + npm

---

## Local development setup

### 1. Add the local domain to your hosts file (one-time)

```bash
echo "127.0.0.1 pdf-invoice.local" | sudo tee -a /etc/hosts
```

### 2. Configure environment (optional)

```bash
cp .env.example .env
```

The defaults in `.env.example` work out of the box. Edit `.env` if you need a different database password or port.

### 3. Install all dependencies

```bash
make setup
```

This installs root Composer deps (phpcs, phpunit, brain/monkey, phpstan), `src/` Composer deps (dompdf), and npm deps (sass, terser, eslint, stylelint). It also installs a pre-push git hook that runs the linter.

### 4. Start WordPress

```bash
make dev
```

Docker spins up MySQL, WordPress, Caddy (HTTPS proxy), and a WP-CLI container that bootstraps the site on first run. Once it finishes, open:

- **Site:** https://pdf-invoice.local
- **Admin:** https://pdf-invoice.local/wp-admin
- **Credentials:** `admin` / `password`

The `src/` directory is bind-mounted directly into WordPress, so any PHP change you make is live immediately - no container restart needed.

### 5. Trust the local CA (one-time, per machine)

On first run your browser will show a certificate warning. To permanently silence it:

```bash
make caddy-trust
```

This extracts Caddy's root CA and adds it to your system trust store. Restart your browser once after running it.

To stop the stack:

```bash
make dev-stop
```

To wipe everything (volumes, DB) and start fresh:

```bash
make env-reset
```

---

## All make targets

| Command | Description |
|---|---|
| `make setup` | First-time install - Composer, npm, git hooks |
| `make dev` | Start local WordPress on https://pdf-invoice.local |
| `make caddy-trust` | Trust Caddy's local CA (run once per machine) |
| `make dev-stop` | Stop the dev stack |
| `make env-reset` | Wipe all Docker volumes |
| `make assets` | Compile SCSS and minify JS |
| `make watch` | Watch SCSS for changes |
| `make phpcs` | Run PHP_CodeSniffer and report errors |
| `make phpcbf` | Auto-fix PHP code with PHP Code Beautifier and Fixer |
| `make lint-js` | Lint JavaScript with ESLint |
| `make lint-css` | Lint SCSS with Stylelint |
| `make lint-all` | Run all linters (PHP, JS, SCSS) |
| `make analyse` | Run PHPStan static analysis |
| `make test-unit` | Run PHPUnit tests (no WP stack needed) |
| `make test-e2e` | Run Playwright E2E tests (full stack) |
| `make check` | Verify version strings are consistent |
| `make version V=x.y.z` | Bump version in all files |
| `make zip` | Build the free plugin zip into `dist/` |
| `make zip-pro` | Build the pro add-on zip into `dist/` |
| `make dist` | Build both zips |
| `make release V=x.y.z` | Full release - version bump, lint, zip |
| `make pot` | Regenerate the `.pot` translation file |
| `make tag V=x.y.z` | Create an annotated git tag |
| `make changelog` | Print commits since the last tag |

---

## Project structure

```
├── src/                    - plugin source (everything that ships in the zip)
│   ├── assets/             - compiled CSS/JS and source SCSS/JS
│   ├── docs/               - in-plugin documentation page
│   ├── includes/           - PHP classes
│   ├── languages/          - translation files
│   ├── pro/                - pro add-on source
│   ├── templates/          - invoice and packing slip templates
│   ├── vendor/             - runtime Composer deps (dompdf)
│   ├── composer.json       - runtime deps
│   └── wpwing-pdf-invoice-packing-slip-for-woocommerce.php
│
├── docker/
│   ├── caddy/Caddyfile     - Caddy reverse proxy config (HTTPS)
│   ├── php/Dockerfile      - PHPUnit runner image
│   └── wordpress/setup.sh  - WP-CLI bootstrap script
├── tests/
│   ├── bootstrap.php       - PHPUnit bootstrap
│   ├── Unit/               - PHPUnit unit tests
│   └── e2e/                - Playwright E2E tests
│
├── composer.json           - dev deps (phpcs, phpunit, brain/monkey, phpstan)
├── docker-compose.yml      - full dev/test stack
├── Makefile                - all dev commands
├── package.json            - npm build scripts
├── phpcs.xml               - coding standards config
└── phpunit.xml             - test runner config
```

---

## Building a release zip

```bash
make zip
```

The zip is written to `dist/wpwing-pdf-invoice-packing-slip-for-woocommerce.zip`. It contains only the distributable plugin files - no `docker/`, `tests/`, dev `vendor/`, or source SCSS/JS.

To verify the contents:

```bash
unzip -l dist/wpwing-pdf-invoice-packing-slip-for-woocommerce.zip | head -30
```

---

## Contributing

Bugs and pull requests are welcome on [GitHub](https://github.com/wpwing-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce).

Before submitting, run:

```bash
make lint-all
make analyse
make check
make test-unit
```
