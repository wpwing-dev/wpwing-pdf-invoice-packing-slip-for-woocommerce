# Competitor Tracking

Reference doc for competitive analysis when planning updates and the freemium model.

**This file is NOT loaded automatically.** Ask Claude to "review the competitor doc"
when planning a release, and it will read this and re-fetch the live pages (numbers below are snapshots and go stale).

- Our plugin: **PDF Invoice and Packing Slip for WooCommerce** (wpwing / voboghure)
- Our version at last update: 1.12.0 (2026-07-19) - Requires PHP 7.4, WP 4.8+, tested to WP 7.0
- Our wordpress.org stats: **10+ installs, 5.0 rating (1 review)**
- Last snapshot of this doc: 2026-08-22

---

## Quick comparison

| Plugin | Vendor | Installs | Rating | Model | Pro pricing |
|---|---|---|---|---|---|
| [PDF Invoices & Packing Slips](#1-wp-overnight-the-market-leader) | WP Overnight | 300,000+ | 5.0 (1,860) | Freemium (extensions) | Bundle EUR 99/yr (1 site), 199/yr (3), 399/yr (25) |
| [WebToffee PDF Invoices](#2-webtoffee-the-2-player) | WebToffee | 50,000+ | 4.9 (284) | Freemium | $69/yr (1 site), $99/yr (5), $199/yr (25) |
| [Print Invoice & Delivery Notes](#3-tyche-softwares-free-only) | Tyche Softwares | 30,000+ | 4.4 (137) | 100% free (no paid tier) | n/a |
| [Challan](#4-webappick-challan-closest-freemium-peer) | WebAppick | 6,000+ | 4.5 (45) | Freemium | ~$29/yr (promo, was $49) |
| [WooCommerce PDF Invoices](#7-woocommercecom-official-marketplace) | Andrew Benbow | 5,000+ | 2.4 (23) | Paid only | $79/yr, $126.40/2yr |
| [PDF Invoice + DnD Builder](#5-add-onsorg-the-newcomer) | add-ons.org | 400+ | 5.0 (4) | Freemium | Not public (verify) |
| [YITH PDF Invoice](#6-yith-premium-suite-play) | YITH | n/a (premium only) | n/a | Premium only | Hidden - shown in cart only (verify) |
| **Ours** | WPWing | **10+** | 5.0 (1) | Freemium (Pro in dev) | TBD - suggested $39-49/yr lane |

---

## 1. WP Overnight (the market leader)

- URL: https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
- Pro/extensions: https://wpovernight.com/
- Installs: 300,000+ | Rating: 5.0 (1,860 reviews) | Last updated: 2026-08-21 | Version: 5.16.1
- Recent changelog: DOMPDF 3.1.6, EDI category O seller identification, Peppol Endpoint ID
  derivation logging, WooCommerce 11.0 compatibility - the e-invoicing investment continues
- Requires: WP 4.4+, PHP 7.4+, WC 3.3+ | Tested: **WP 7.1**

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

**Why they matter:** the benchmark. Match table-stakes parity with their free tier;
differentiate on UX, support responsiveness, and pricing.

---

## 2. WebToffee (the #2 player)

- URL: https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce/
- Pro: https://www.webtoffee.com/product/woocommerce-pdf-invoices-packing-slips/
- Installs: **50,000+** | Rating: 4.9 (284 reviews) | Last updated: 2026-08-20 | Version: 5.0.1
- Requires: WP 6.0+, PHP 5.6+ | Tested: WP 7.1, PHP 8.4
- Pro pricing: **$69/yr (1 site), $99/yr (5 sites), $199/yr (25 sites)**

**Free features:** PDF invoice, packing slip, **delivery note, shipping label, dispatch
label**, **UBL and XML invoice generation**, customizable templates with logo/branding,
custom number sequences, bulk printing, auto email attachment, RTL + Unicode via free
add-on, customer My Account downloads, tax display, **custom PDF filenames**, skip
free-order invoices.

**Pro:** credit notes, advanced template customization with code editors, multiple invoice
templates, **PrintNode integration (remote printing)**, custom fields, shipping label size
customization, address labels (multiple types), **picklists**, proforma invoices,
advanced grouping/filtering, **Pay Now link on invoices**, Dropbox backup, WPML
multilingual, bulk ZIP export with filters.

**Why they matter:** the strongest direct freemium competitor after WP Overnight, and the
most complete free document lineup (5 document types + UBL free). Their $69/yr is the
mid-market price anchor. Their free tier is the parity bar for our document types.

---

## 3. Tyche Softwares (free-only)

- URL: https://wordpress.org/plugins/woocommerce-delivery-notes/
- Installs: 30,000+ | Rating: 4.4 (137 reviews) | Last updated: 2026-08-04 | Version: 7.3.0
- Requires: WP 6.0+, PHP 7.4+, WC 5.0+
- Support responsiveness: 8 of 20 threads resolved in last two months (weak)

**Free features (no paid tier at all):** five document types - **invoices, delivery notes,
receipts, credit notes, packing slips** - individual and bulk printing, email attachments,
customizable templates with branding/numbering, customer access via My Account and emails,
sequential numbering with yearly reset, **PDF storage with configurable expiration**,
live template preview, product images, zoom controls, developer hooks.

**Monetization:** none on this plugin - it funnels users to 13 unrelated Tyche pro plugins
(bookings, abandoned cart, delivery dates, deposits, etc.).

**Why they matter:** they set free-tier expectations high - receipts and credit notes cost
nothing here. Anyone comparing free tiers sees five document types. Their weak support
resolution and 4.4 rating are the opening: match the document lineup, beat them on polish
and support. Also a reference for PDF retention/expiration settings.

---

## 4. WebAppick "Challan" (closest freemium peer)

- URL: https://wordpress.org/plugins/webappick-pdf-invoice-for-woocommerce/
- Pro: https://webappick.com/plugin/woocommerce-pdf-invoice-packing-slips/
- Installs: 6,000+ (was 4,000+ in July - growing) | Rating: 4.5 (45 reviews)
- Last updated: **2026-05-21 (3 months stale)** | Version: 3.7.85
- Requires: WP 4.4+, PHP 7.4+ | Tested: WP 7.0.4
- Pro pricing: ~$29/yr promo (regular $49) - via search results, verify on their site
  (webappick.com blocks direct fetch with 403)

**Free features:** auto PDF invoice + email attach, packing slips, shipping labels &
delivery address labels, customizable templates with logo, multiple paper sizes (A4, A5,
A3, Letter), 15+ language fonts (Bengali, Arabic, Hindi, Chinese, Japanese), bulk download,
customer downloads, RTL, custom CSS + developer hooks.

**Pro:** 10+ premium templates, invoices in customer's order language (WPML/Polylang/Weglot),
credit notes for refunds, proforma invoices, ZATCA e-invoicing (Saudi), GST (India), custom
fields, product images, digital signatures, 20+ stamp designs, Dropbox backup, barcode/QR,
WooCommerce Subscriptions support, priority support.

**Why they matter:** similar scale ambitions and a regional/multilingual focus that overlaps
ours. Growing despite a 3-month release gap - but that gap is also our chance to outpace
them on freshness. Their Pro list remains a good map of what converts to paid.

---

## 5. add-ons.org (the newcomer)

- URL: https://wordpress.org/plugins/pdf-for-woocommerce/
- Installs: 400+ | Rating: 5.0 (4 reviews) | Version: 7.1.0
- Last verified: 2026-07-11 (not re-fetched this round)
- Requires: WP 2.0+, PHP 7.0+ | Tested: WP 7.0

**Free features:** drag-and-drop template builder (no code), invoices/packing slips/shipping
labels/delivery notes/vouchers, QR + barcodes, dynamic data insertion (order/customer/product),
table/text/image/HTML/page-break elements, customer downloads, RTL + multilingual, custom
fonts & paper sizes, shortcodes/merge tags, real-time preview.

**Pro (pricing not public):** advanced QR/barcode, watermarks, header/footer, conditional
logic, priority support, 1-year support, 30-day money-back guarantee.

**Why they matter:** small but the **drag-and-drop builder** is a strong differentiator and
ships in free. Very fast release cadence. If template editing becomes a buying factor, this
is the one to watch.

---

## 6. YITH (premium suite play)

- URL: https://yithemes.com/themes/plugins/yith-woocommerce-pdf-invoice/
- Model: **premium only** - the wordpress.org free version was permanently closed on
  2021-09-14 at the author's request (last free version 1.3.0)
- Last verified: 2026-07-11 (not re-fetched this round)
- Pricing: still not shown on landing page - **verify exact annual price in cart**

**Features:** auto/manual generation with triggers, sequential numbering + prefix/suffix,
management dashboard with bulk download/regenerate, packing slips, credit notes, VAT/SSN
checkout fields, 9 templates + Gutenberg builder, Dropbox + Google Drive backup, Italian
e-invoicing XML, customer downloads, deep integration with the YITH plugin ecosystem.

**Why they matter:** ecosystem lock-in - they upsell across many YITH plugins. Less a
feature threat, more a bundling/pricing-strategy reference for the freemium model.

---

## 7. WooCommerce.com (official marketplace)

- URL: https://woocommerce.com/products/pdf-invoices/
- Vendor: Andrew Benbow | Model: paid only (no free version)
- Pricing: **$79/year**, or $126.40/2-year (20% off)
- Installs: 5,000+ | Rating: **2.4 (23 reviews)** | Version: 5.1.2
- Last verified: 2026-07-11 (not re-fetched this round)

**Features:** auto-create & attach PDF invoice to completed-order email, sequential numbering
with custom formatting, logo, date formats, customer account downloads, admin resend/download,
company legal/tax info sections, invoice-number column in order list, invoice metabox.

**Reputation note:** recent reviews repeatedly cite non-existent support and a year without
updates despite subscription fees. Weak incumbent at a high price point.

**Why they matter:** sets a **price anchor** ($79/yr) for a relatively basic feature set,
sold through the official WooCommerce marketplace.

---

## Cross-market observations (2026-08-22)

- **Free-tier document parity bar** is now: invoice, packing slip, delivery note,
  shipping label (WebToffee, Challan, add-ons.org all free). Tyche even gives receipts
  and credit notes free. We have 3 of these; shipping labels is the biggest visible gap.
- **UBL/XML e-invoicing is drifting into free tiers** (WP Overnight and WebToffee both).
  Still out of scope per Phase E, but the pressure is real and growing.
- **Pro price ladder:** Challan ~$29 -> our suggested $39-49 -> WebToffee $69 ->
  WooCommerce.com $79 -> WP Overnight EUR 99. The $39-49 lane is still open.
- **Freshness:** WP Overnight and WebToffee ship near-weekly; Challan is 3 months stale.
  Our last release is over a month old - cadence is a competitive weapon we control.
- **Tested-up-to:** leaders are on WP 7.1; we declare 7.0. Bump it.

---

## How to use this doc when planning

1. Tell Claude: "review docs/COMPETITORS.md and re-fetch the pages."
2. Ask for: feature gaps in our free tier vs. competitor free tiers; which Pro features
   convert across competitors (candidates for our paid tier); price positioning.
3. After any review, update the snapshot numbers and the date above.

### Verify / TODO on next review

- YITH exact annual price (cart checkout - page and search both hide it).
- add-ons.org Pro pricing (not published anywhere found); re-fetch their page (skipped
  this round).
- Confirm Challan Pro $29/yr on webappick.com directly (site 403s automated fetch).
- Re-fetch WooCommerce.com marketplace listing (skipped this round).
- Check whether Challan ships a release after 2026-05-21 (possible decline signal).
