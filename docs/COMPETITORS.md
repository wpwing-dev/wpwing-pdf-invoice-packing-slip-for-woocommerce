# Competitor Tracking

Reference doc for competitive analysis when planning updates and the freemium model.

**This file is NOT loaded automatically.** Ask Claude to "review the competitor doc"
when planning a release, and it will read this and re-fetch the live pages (numbers below
are snapshots and go stale).

- Our plugin: **PDF Invoice and Packing Slip for WooCommerce** (wpwing / voboghure)
- Our version at last update: 1.9.0 - Requires PHP 7.4, WP 4.8+, WC 4.5+
- Last snapshot of this doc: 2026-06-28

---

## Quick comparison

| Plugin | Vendor | Installs | Rating | Model | Pro pricing |
|---|---|---|---|---|---|
| [PDF Invoices & Packing Slips](#1-wp-overnight-the-market-leader) | WP Overnight | 300,000+ | 5.0 (1,857) | Freemium (extensions) | Paid extensions/bundles (verify) |
| [Challan](#2-webappick-challan-closest-peer) | WebAppick | 4,000+ | 4.5 (45) | Freemium | Not public (verify) |
| [PDF Invoice + DnD Builder](#3-add-onsorg-the-newcomer) | add-ons.org | 400+ | 5.0 (4) | Freemium | Not public (verify) |
| [YITH PDF Invoice](#4-yith-premium-suite-play) | YITH | n/a (premium) | n/a | Premium (free version exists) | ~annual, verify on cart |
| [WooCommerce PDF Invoices](#5-woocommercecom-official-marketplace) | Andrew Benbow | n/a | n/a | Paid only | $79/yr, $126.40/2yr |

---

## 1. WP Overnight (the market leader)

- URL: https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
- Pro/extensions: https://wpovernight.com/
- Installs: 300,000+ | Rating: 5.0 (1,857 reviews) | Last updated: 2026-06-08
- Requires: WP 4.4+, PHP 7.4+, WC 3.3+ | Tested: WP 7.0

**Free features:** auto-attach PDF/XML invoices to order emails, download/print invoices &
packing slips from admin, bulk generation, fully customizable HTML/CSS templates, sequential
numbering, 35+ locales, customer downloads via My Account.

**Standout:** e-document formats - UBL 2.1, Peppol BIS 3.0, CII D16B, Factur-X 1.0,
ZUGFeRD 1.0/2.0 (EU e-invoicing compliance).

**Pro/extensions:** proforma invoices, credit notes, cloud storage, automatic printing,
VAT compliance.

**Why they matter:** the benchmark. Most-installed by 75x. Match table-stakes parity with
their free tier; differentiate on UX, support responsiveness, and pricing.

---

## 2. WebAppick "Challan" (closest peer)

- URL: https://wordpress.org/plugins/webappick-pdf-invoice-for-woocommerce/
- Installs: 4,000+ | Rating: 4.5 (45 reviews) | Last updated: 2026-05-21
- Requires: WP 4.4+, PHP 7.4+ | Tested: WP 7.0

**Free features:** auto PDF invoice + email attach, packing slips, shipping labels &
delivery notes, customizable templates with logo, 15+ language fonts (Bengali, Arabic,
Hindi, Chinese, Japanese), bulk download as ZIP or merged PDF, customer downloads, RTL,
custom CSS + developer hooks.

**Pro:** premium templates, ZATCA e-invoicing (Saudi), GST (India), custom fields,
product images, digital signatures, barcode/QR codes, subscription invoicing.

**Why they matter:** similar scale and a regional/multilingual focus that overlaps ours.
Watch their Pro feature list - it's a good map of what converts to paid in this segment.

---

## 3. add-ons.org (the newcomer)

- URL: https://wordpress.org/plugins/pdf-for-woocommerce/
- Installs: 400+ | Rating: 5.0 (4 reviews) | Last updated: ~weekly cadence
- Requires: WP 2.0+, PHP 7.0+ | Tested: WP 7.0

**Free features:** drag-and-drop template builder (no code), invoices/packing slips/shipping
labels/delivery notes/vouchers/tickets, QR + barcodes, conditional show/hide logic, dynamic
data insertion, customer downloads, RTL + multilingual, custom fonts & paper sizes,
shortcodes/merge tags, real-time preview.

**Why they matter:** small but the **drag-and-drop builder** is a strong differentiator and
ships in free. If template editing becomes a buying factor, this is the one to watch.

---

## 4. YITH (premium suite play)

- URL: https://yithemes.com/themes/plugins/yith-woocommerce-pdf-invoice/
- Model: premium (a limited free version also exists on wordpress.org)
- Pricing: not shown on landing page - **verify exact annual price in cart**

**Features:** auto/manual generation with triggers, sequential numbering + prefix/suffix,
management dashboard with bulk download/regenerate, packing slips, credit notes, VAT/SSN
checkout fields, 9 templates + Gutenberg builder, Dropbox + Google Drive backup, Italian
e-invoicing XML, customer downloads, deep integration with the YITH plugin ecosystem.

**Why they matter:** ecosystem lock-in - they upsell across many YITH plugins. Less a
feature threat, more a bundling/pricing-strategy reference for the freemium model.

---

## 5. WooCommerce.com (official marketplace)

- URL: https://woocommerce.com/products/pdf-invoices/
- Vendor: Andrew Benbow | Model: paid only (no free version)
- Pricing: **$79/year**, or $126.40/2-year (20% off)

**Features:** auto-create & attach PDF invoice to completed-order email, sequential numbering
with custom formatting, logo, date formats, customer account downloads, admin resend/download,
company legal/tax info sections, invoice-number column in order list (filter-customizable),
invoice metabox on order edit.

**Why they matter:** sets a **price anchor** ($79/yr) for a relatively basic feature set,
sold through the official WooCommerce marketplace. Useful reference when pricing our Pro tier.

---

## How to use this doc when planning

1. Tell Claude: "review docs/COMPETITORS.md and re-fetch the pages."
2. Ask for: feature gaps in our free tier vs. competitor free tiers; which Pro features
   convert across competitors (candidates for our paid tier); price positioning.
3. After any review, update the snapshot numbers and the date above.

### Verify / TODO on next review
- YITH exact annual price (cart).
- WP Overnight per-extension and bundle pricing.
- WebAppick Challan Pro pricing.
- add-ons.org Pro pricing.
