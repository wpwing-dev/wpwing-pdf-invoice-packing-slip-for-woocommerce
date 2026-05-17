# Changelog

All notable changes to this project are documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versions follow [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Added
- Pro addon plugin with access control, proforma invoice, and auto credit note on refund
- Template selection system with preview modal and Modern template design
- Structured company fields (address, city, ZIP, country, phone, email, VAT)
- Paper size selection (A4 / Letter)
- Shipping address display on invoice
- Product SKU column on invoice
- Yearly invoice number reset
- Skip invoice for free/zero-total orders
- Auto-generate invoice on order status change
- Email attachment integration (attach PDFs to WooCommerce transactional emails)
- Bulk PDF generation from orders list
- Invoice status column on orders list
- `make dist` command to build both free and pro plugin zips

### Changed
- Build system replaced: Laravel Mix → `sass` + `terser` with Makefile workflow
- All document actions secured with nonces (CSRF protection)
- Metabox buttons now have distinct labels ("View Invoice" / "Cancel Invoice")
- My Account button uses WooCommerce native action array instead of injected HTML

### Fixed
- Build reproducibility: committed `composer.lock` and `package-lock.json`
- `make zip` no longer mutates local `vendor/`; installs prod vendor inside build dir

---

## [1.5.0] — 2024-xx-xx

### Added
- HPOS (High-Performance Order Storage) compatibility

### Changed
- Version bump to 1.5.0

---

## [1.4.3] — 2024-xx-xx

### Changed
- Version bump and compatibility updates

---

## [1.3.3] — 2023-xx-xx

### Changed
- Refactor and code cleanup

---

## [1.3.2] — 2023-xx-xx

### Fixed
- Deactivate gracefully if WooCommerce is not loaded

---

## [1.3.1] — 2023-xx-xx

### Added
- Show/hide settings UI

---

[Unreleased]: https://github.com/voboghure-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce/compare/HEAD...HEAD
