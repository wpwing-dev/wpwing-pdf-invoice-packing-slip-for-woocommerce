# Competitor Tracking

Reference doc for competitive analysis when planning updates and the freemium model.

**This file is NOT loaded automatically.** Ask Claude to "review the competitor doc"
when planning a release, and it will read this and re-fetch the live pages (numbers below are snapshots and go stale).

- Our plugin: **PDF Invoice and Packing Slip for WooCommerce** (wpwing / voboghure)
- Our version at last update: 1.9.0 - Requires PHP 7.4, WP 4.8+, WC 4.5+
- Last snapshot of this doc: 2026-07-03

---

## Quick comparison

| Plugin | Vendor | Installs | Rating | Model | Pro pricing |
|---|---|---|---|---|---|
| [PDF Invoices & Packing Slips](#1-wp-overnight-the-market-leader) | WP Overnight | 300,000+ | 5.0 (1,858) | Freemium (extensions) | Bundle EUR 99/yr (1 site), 199/yr (3), 399/yr (25) |
| [Challan](#2-webappick-challan-closest-peer) | WebAppick | 4,000+ | 4.5 (45) | Freemium | ~$29/yr (promo, was $49) |
| [PDF Invoice + DnD Builder](#3-add-onsorg-the-newcomer) | add-ons.org | 400+ | 5.0 (4) | Freemium | Not public (verify) |
| [YITH PDF Invoice](#4-yith-premium-suite-play) | YITH | n/a (premium only) | n/a | Premium only | Hidden - shown in cart only (verify) |
| [WooCommerce PDF Invoices](#5-woocommercecom-official-marketplace) | Andrew Benbow | 5,000+ | 2.4 (23) | Paid only | $79/yr, $126.40/2yr |

---

## 1. WP Overnight (the market leader)

- URL: https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
- Pro/extensions: https://wpovernight.com/
- Installs: 300,000+ | Rating: 5.0 (1,858 reviews) | Last updated: 2026-06-29 | Version: 5.15.0
- Requires: WP 4.4+, PHP 7.4+, WC 3.3+ | Tested: WP 7.0
- Support responsiveness: 22 of 28 forum issues resolved in last two months

**Free features:** auto-attach PDF/XML invoices to order emails, download/print invoices &
packing slips from admin, bulk generation, fully customizable HTML/CSS templates, sequential
numbering, 35+ locales, customer downloads via My Account.

**Standout:** e-document formats - UBL 2.1, Peppol BIS 3.0, CII D16B, Factur-X 1.0,
ZUGFeRD 1.0/2.0 (EU e-invoicing compliance).

**Pro/extensions (bundle EUR 99/199/399 per year for 1/3/25 sites):**
- Professional: proforma invoices, credit notes, receipts, custom document titles/filenames,
  order notification emails, bulk export by date/status/customer, Dropbox + FTP/SFTP storage,
  multilingual (WPML, Polylang, Weglot, TranslatePress, GTranslate).
- Premium Templates: 2 premium templates, drag-and-drop customizer, extensive column options
  (SKU, thumbnails, tax rates, custom fields), flexible totals.
- Separate add-ons: Peppol network delivery, automatic order printing, EU VAT compliance.

**Why they matter:** the benchmark. Most-installed by 75x. Match table-stakes parity with
their free tier; differentiate on UX, support responsiveness, and pricing.

---

## 2. WebAppick "Challan" (closest peer)

- URL: https://wordpress.org/plugins/webappick-pdf-invoice-for-woocommerce/
- Pro: https://webappick.com/plugin/woocommerce-pdf-invoice-packing-slips/
- Installs: 4,000+ | Rating: 4.5 (45 reviews) | Last updated: 2026-05-21 | Version: 3.7.85
- Requires: WP 4.4+, PHP 7.4+ | Tested: WP 7.0
- Pro pricing: ~$29/yr promo (regular $49) - via search results, verify on their site
  (webappick.com blocks direct fetch with 403)

**Free features:** auto PDF invoice + email attach, packing slips, shipping labels &
delivery notes, customizable templates with logo, multiple paper sizes (A4, A5, A3, Letter),
15+ language fonts (Bengali, Arabic, Hindi, Chinese, Japanese), bulk download as ZIP or
merged PDF, customer downloads, RTL, custom CSS + developer hooks.

**Pro:** 10+ premium templates, invoices in customer's order language (WPML/Polylang/Weglot),
credit notes for refunds, proforma invoices, ZATCA e-invoicing (Saudi), GST (India), custom
fields, product images, digital signatures, 20+ stamp designs, Dropbox backup, barcode/QR,
WooCommerce Subscriptions support, priority support.

**Why they matter:** similar scale and a regional/multilingual focus that overlaps ours.
Watch their Pro feature list - it's a good map of what converts to paid in this segment.

---

## 3. add-ons.org (the newcomer)

- URL: https://wordpress.org/plugins/pdf-for-woocommerce/
- Installs: 400+ | Rating: 5.0 (4 reviews) | Last updated: 2026-07-03 (ships near-daily) | Version: 7.1.0
- Requires: WP 2.0+, PHP 7.0+ | Tested: WP 7.0

**Free features:** drag-and-drop template builder (no code), invoices/packing slips/shipping
labels/delivery notes/vouchers, QR + barcodes, dynamic data insertion (order/customer/product),
table/text/image/HTML/page-break elements, customer downloads, RTL + multilingual, custom
fonts & paper sizes, shortcodes/merge tags, real-time preview.

**Pro (pricing not public):** advanced QR/barcode, watermarks, header/footer, conditional
logic, priority support, 1-year support, 30-day money-back guarantee. Note: conditional
logic and watermarks now gated to Pro (previously listed as free features).

**Why they matter:** small but the **drag-and-drop builder** is a strong differentiator and
ships in free. Very fast release cadence. If template editing becomes a buying factor, this
is the one to watch.

---

## 4. YITH (premium suite play)

- URL: https://yithemes.com/themes/plugins/yith-woocommerce-pdf-invoice/
- Model: **premium only** - the wordpress.org free version was permanently closed on
  2021-09-14 at the author's request (last free version 1.3.0)
- Pricing: still not shown on landing page - **verify exact annual price in cart**
  (Bluehost bundle "up to 30% off" mentioned)

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
- Installs: 5,000+ | Rating: **2.4 (23 reviews)** | Version: 5.1.2 | Tested: WC 10.8.0

**Features:** auto-create & attach PDF invoice to completed-order email, sequential numbering
with custom formatting, logo, date formats, customer account downloads, admin resend/download,
company legal/tax info sections, invoice-number column in order list (filter-customizable),
invoice metabox on order edit.

**Reputation note:** recent reviews repeatedly cite non-existent support and a year without
updates despite subscription fees. Weak incumbent at a high price point.

**Why they matter:** sets a **price anchor** ($79/yr) for a relatively basic feature set,
sold through the official WooCommerce marketplace. Useful reference when pricing our Pro tier.

---

## How to use this doc when planning

1. Tell Claude: "review docs/COMPETITORS.md and re-fetch the pages."
2. Ask for: feature gaps in our free tier vs. competitor free tiers; which Pro features
   convert across competitors (candidates for our paid tier); price positioning.
3. After any review, update the snapshot numbers and the date above.

### Verify / TODO on next review
- YITH exact annual price (cart checkout - page and search both hide it).
- add-ons.org Pro pricing (not published anywhere found).
- Confirm Challan Pro $29/yr on webappick.com directly (site 403s automated fetch).
- Our GitHub issues #3 and #4 are already shipped (1.5.0) - close them.
