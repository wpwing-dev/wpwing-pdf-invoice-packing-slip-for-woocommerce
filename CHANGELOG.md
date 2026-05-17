# Changelog

All notable changes to this project are documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versions follow [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

---

## [1.5.0] — unreleased

### Added
- HPOS (High-Performance Order Storage) compatibility
- Auto-generate invoice and packing slip on configurable order status change
- Email attachment: attach invoice/packing slip PDF to any WooCommerce transactional email
- Bulk PDF generation from the WooCommerce orders list via bulk actions
- Invoice and packing slip status column on the orders list
- Structured company information fields: address, city, ZIP, country, phone, email, VAT/tax ID
- Paper size selection (A4 or Letter)
- Option to display shipping address on invoice
- Product SKU column on invoice (configurable)
- Yearly invoice number reset option
- Option to skip invoice generation for free (zero-total) orders
- Multiple template selection — Default and Modern templates built-in
- Live invoice and packing slip preview modal in Template settings
- GitHub Actions CI pipeline: PHP lint, syntax check, asset build, version consistency check
- Makefile targets: `make check`, `make tag`, `make setup`, `make changelog`, `make dist`

### Changed
- Build system replaced: Laravel Mix → `sass` + `terser` with lean Makefile-driven workflow
- All document actions (create, view, cancel) secured with WordPress nonces (CSRF protection)
- Metabox buttons now show distinct labels: "View Invoice" / "Cancel Invoice"
- My Account invoice button uses WooCommerce native order action array (fixes flex layout conflict)
- Cancel action now shows a confirmation dialog before proceeding
- Admin notices confirm success or failure after every document action
- `make release` now runs version consistency check and prints git/tag next-step instructions

### Fixed
- Build reproducibility: `composer.lock` and `package-lock.json` now committed to git
- `make zip` no longer strips dev dependencies from local `vendor/`; prod vendor is installed inside an isolated build directory

---

## [1.4.3] — 2024-04-22

### Changed
- Compatibility check with WordPress 6.5 and WooCommerce 8.8

---

## [1.4.2] — 2024-02-17

### Changed
- Compatibility check with WooCommerce latest version

---

## [1.4.1] — 2024-01-20

### Changed
- Updated Dompdf library to v2.0.4
- Compatibility check with WordPress and WooCommerce latest versions

---

## [1.4.0] — 2023-06-17

### Changed
- Removed "WPWing" prefix from the plugin name
- Invoice and packing slip now open in a new browser tab

---

## [1.3.4] — 2023-04-17

### Changed
- Refactored and updated the Dompdf library

---

## [1.3.3] — 2022-07-12

### Changed
- Refactor and code cleanup

---

## [1.3.2] — 2022-06-17

### Fixed
- Plugin now deactivates gracefully if WooCommerce is not loaded

---

## [1.3.1] — 2022-06-08

### Added
- Invoice download link in customer My Account > Orders section

---

## [1.3.0] — 2022-05-08

### Changed
- Updated invoice settings template UI/UX
- Updated Dompdf to v1.2.2

---

## [1.2.0] — 2022-02-04

### Added
- Global setting to send invoice PDF to customer billing email

### Fixed
- Corrected checkbox label text in Invoice settings

### Changed
- Updated Dompdf to v1.1.1

---

## [1.1.0] — 2021-12-25

### Added
- Send PDF invoice as email attachment when admin creates an invoice

---

## [1.0.1] — 2021-10-15

### Changed
- Settings text improvements
- Default invoice number now set automatically if left empty

---

## [1.0.0] — 2021-10-14

- Initial release

---

[Unreleased]: https://github.com/voboghure-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce/compare/v1.5.0...HEAD
[1.5.0]: https://github.com/voboghure-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce/releases/tag/v1.5.0
[1.4.3]: https://github.com/voboghure-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce/releases/tag/v1.4.3
