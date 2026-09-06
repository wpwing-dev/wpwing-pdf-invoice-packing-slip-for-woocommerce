# Product Release Roadmap: Sept 2026 - Mar 2027 (Q3/Q4 2026 + Q1 2027)

**Target Plugin:** PDF Invoice and Packing Slip for WooCommerce (`wpwing-pdf-invoice-packing-slip-for-woocommerce`)
**Current Baseline:** v1.14.0 shipped
**Release Cadence:** Biweekly (every 2 weeks) September - December 2026, tapering to monthly January - March 2027 once the named feature backlog is placed and there's no external deadline forcing pace. 7-month runway: 2026-09-07 to 2027-03-29.
**Core Objective:** Free Growth & Directory Health only, this cycle. Close remaining document parity gaps against WebToffee, Challan, and Tyche; maintain current WordPress/WooCommerce compatibility declarations; deliver on public readme.txt roadmap commitments to protect 5-star ratings.

**No Pro launch this cycle.** Pro development (Proforma advanced logic, Credit Notes, licensing, filtered export, cloud sync, multilingual, column customizer) continues as an unscheduled background track - see Section 5. There is no v2.0.0 milestone, no BFCM-anchored launch, and no commercial campaign date committed in this document.

---

## 0. Revision Notes

This is the second revision of this plan:

- **v1 (original Gemini draft):** weekly releases, culminating in a BFCM-anchored Pro v2.0 commercial launch on 2026-11-16. Written outside the repo, with two factual errors: it treated `Tested up to: 7.1`/`WC tested up to: 11.0.1` as open work (already shipped) and assumed Proforma didn't exist yet (it does, Pro-only, in `src/pro/includes/class-wcpdf-proforma.php`).
- **v2 (previous revision):** fixed those errors and relaxed cadence to biweekly, but still anchored a Pro v2.0 launch to BFCM 2026.
- **v3 (this revision):** removes the Pro launch from this cycle entirely, per explicit decision - no Pro release in 2026. Cadence no longer needs a BFCM-driven weekly compression anywhere, so it's uniformly biweekly, tapering to monthly once the concrete backlog of named free-tier features runs out (roughly the December/January boundary).
- Before starting any release below, re-verify its listed scope against current file state - plans drift, this one has drifted twice already.

---

## 1. Strategic Principles

- **The Proforma Solution:** The public `readme.txt` roadmap section promises "Proforma Invoice generation" and "Print-ready Shipping Labels" with no free/pro split stated. Gating Proforma entirely behind Pro (its current state) risks community backlash - R4 ships a free-tier Proforma regardless of Pro launch timing.
- **Directory Freshness:** Regular releases signal an actively maintained plugin, but cadence should not outpace real QA capacity - biweekly with a docker `verify` pass per release, tapering to monthly once there's no concrete backlog item queued, rather than inventing filler features to keep a fixed cadence.
- **BFCM without a Pro launch:** Black Friday (2026-11-27) / Cyber Monday (2026-11-30) still drives a real order-volume spike for every store running the *free* plugin - bulk PDF generation, high-throughput email attachments. R7 (2026-11-30) is reserved as a stability/support buffer for that reason, even though there is no commercial launch to support.
- **Pricing Ladder (reference only, not committed to this cycle):** When a Pro launch is eventually scheduled, the target position is between Challan (~$29) and WebToffee ($69) / WooCommerce.com ($79) / WP Overnight (€99): Single Site $49/yr, 5 Sites $89/yr, 25 Sites $169/yr.

---

## 2. Release Overview (13 releases, free-tier only)

| # | Date (Mon) | Target Version | Primary Scope & Headline Feature |
| :-- | :--- | :--- | :--- |
| R1 | 2026-09-07 | v1.15.0 | Shipping Labels document type |
| R2 | 2026-09-21 | v1.16.0 | Product Thumbnails in Item Tables + Dompdf scaling safeguard |
| R3 | 2026-10-05 | v1.17.0 | Dynamic Filename Token Builder + WC Analytics Invoice# column |
| R4 | 2026-10-19 | v1.18.0 | Free Proforma Invoice + free/Pro proforma architecture split |
| R5 | 2026-11-02 | v1.19.0 | Eco Ink-Saving Mode + Invoice Summary CSV Export (free tier) |
| R6 | 2026-11-16 | v1.20.0 | "Pay Now" Direct Payment Link + PDF Storage Retention/GDPR hygiene |
| R7 | 2026-11-30 | v1.20.1 | **Buffer/stability sprint** - BFCM order-volume hardening for free-tier users |
| R8 | 2026-12-14 | v1.21.0 | Static file attachment (1) + basic column visibility toggle (SKU/Weight) |
| R9 | 2026-12-28 | v1.22.0 | `.pot` translation refresh + backlog/polish |
| R10 | 2027-01-11 | v1.23.0 | PHP 8.4 & WP 7.2 readiness pass |
| R11 | 2027-02-08 | v1.23.1 | Buffer sprint - support backlog & hardening |
| R12 | 2027-03-08 | v1.24.0 | Backlog-driven parity release (scope set from R7/R11 feedback) |
| R13 | 2027-03-29 | v1.25.0 | Year-in-review polish + publish Q2 2027 roadmap (Pro launch timing revisited here) |

---

## 3. Detailed Implementation Plan

### September - October 2026

#### R1 - 2026-09-07 - v1.15.0: Shipping Labels Document Type
- New `Shipping Label` document type extending `WPWing_WcPdf_Document`, following the same pattern as `WPWing_WcPdf_Delivery` (meta keys, `save()`/`reset()`, template hooks).
- Clean, price-free layout: sender details, prominent recipient shipping address, order weight, QR/barcode (reuse the existing `chillerlan/php-qrcode` dependency already vendored for invoice QR codes).
- Wire into the same 6 places `delivery` touches: `class-wpwing-wcpdf-plugin.php` (action map + factory), `class-wpwing-wcpdf-admin.php` (metabox buttons/notices), `class-wpwing-wcpdf-orders-list.php` (bulk generate/ZIP/merged-PDF actions), `class-wpwing-wcpdf-settings.php` (template + auto-generate-on-status), `class-wpwing-wcpdf-wc-hooks.php` (auto-create on status change), plus `templates/default/shipping-label/` and `templates/modern/shipping-label/`.
- Hardening: nonce checks on the new endpoints from day one.

#### R2 - 2026-09-21 - v1.16.0: Product Thumbnails in Line Items
- Setting toggle to render product featured-image thumbnails inside Invoices, Packing Slips, and Delivery Notes.
- Automatic image downscaling and dimension caching to prevent Dompdf memory exhaustion - test on a 128M PHP memory_limit environment.
- Dimension selector (32x32, 48x48, 64x64).

#### R3 - 2026-10-05 - v1.17.0: Dynamic Filename Patterns & Analytics
- Token-based filename formatting setting supporting `{{doc_type}}`, `{{order_number}}`, `{{invoice_date}}`.
- Dedicated "Invoice Number" column on the WooCommerce Analytics Orders report with a quick-view modal.

#### R4 - 2026-10-19 - v1.18.0: Free Proforma Invoice + Architecture Split
- **Architecture work first:** refactor `WPWing_WcPdf_Proforma` so a free-tier base class (extending `WPWing_WcPdf_Document`, mirroring `WPWing_WcPdf_Delivery`) carries standard order data + standard sequential numbering, and the existing Pro class becomes a subclass/decorator adding independent counters, auto-conversion-on-payment, and custom prefixes/suffixes. Confirm the Pro plugin's activation check gracefully falls back to the free base class when Pro is inactive.
- Free: Proforma selectable for "Pending payment" / "On-hold" orders, prominent "PROFORMA INVOICE" header/watermark.

---

### November - December 2026

#### R5 - 2026-11-02 - v1.19.0: Eco Mode + CSV Export
- Eco ink-saving mode: 1-click toggle stripping solid backgrounds/dark headers/heavy borders to thin line-art; "Print Eco PDF" action in the order metabox.
- Invoice Summary CSV export (Invoice #, Date, Order #, Customer, Subtotal, Tax, Grand Total) - current page/standard batch.

#### R6 - 2026-11-16 - v1.20.0: Pay Now + Storage Hygiene
- "Pay Now" link on unpaid Invoices/Proformas, routed to native `order-pay`.
- PDF storage retention scheduler (purge cached temp PDFs after 30/60/90 days) + disk/temp-dir size check in System Status.

#### R7 - 2026-11-30 - v1.20.1: BFCM Stability Buffer
- No new feature mandated. Watch and patch for high-throughput simultaneous PDF generation and email-attachment load during the Black Friday/Cyber Monday order spike, since every free-tier store sees that traffic whether or not we launch anything.

#### R8 - 2026-12-14 - v1.21.0: Attachments & Column Visibility
- Support attaching 1 static PDF (e.g. Terms of Service) to completed-order emails.
- Basic column visibility checkboxes for SKU and Weight in item tables.

#### R9 - 2026-12-28 - v1.22.0: Translation Refresh & Backlog
- Refresh base `.pot` translation files.
- Remaining scope: whatever support/backlog items accumulated since R1 - deliberately left open rather than pre-assigned.

---

### January - March 2027 (monthly cadence)

#### R10 - 2027-01-11 - v1.23.0: PHP 8.4 & WP 7.2 Readiness
- Full compatibility pass for PHP 8.4 and WordPress 7.2 alpha/beta builds.
- Deprecated hook cleanup, sequential-numbering index optimization on large databases.

#### R11 - 2027-02-08 - v1.23.1: Buffer Sprint
- Support backlog and hardening only. Protect this slot - it's what keeps the schedule honest once the named feature list runs out.

#### R12 - 2027-03-08 - v1.24.0: Backlog-Driven Parity Release
- Scope determined by what R7/R9/R11 actually surfaced (support tickets, review feedback, competitor parity gaps noticed along the way) rather than pre-committed now.

#### R13 - 2027-03-29 - v1.25.0: Year-in-Review & Q2 2027 Roadmap
- Final polish pass across the cycle's features.
- Publish a "Year in Review & Q2 2027 Roadmap" post.
- **This is the checkpoint to decide Pro launch timing**, informed by: how much of the Section 5 background track is actually done, real release velocity from R1-R12, and free-tier install/rating trends.

---

## 4. Operational Checklist for AI Agents & Developers

- **Cadence:** Biweekly Monday releases September - December 2026, monthly January - March 2027. Do not compress back to weekly without a specific reason - there is no BFCM deadline forcing pace this cycle.
- **Verify before ship:** Run the `verify` skill (build + launch in the docker stack, exercise the feature end-to-end) before tagging any release.
- **Reality-check the plan first:** Before starting a release, confirm this document's assumed starting state against the actual code - this plan has drifted from reality twice already (see Revision Notes).
- **Changelog Rule:** Every release must contain at least one bug fix or hardening item alongside the headline feature, except the explicit buffer sprints (R7, R11), whose entire scope *is* bug fixes/hardening.
- **Compatibility Watch:** Update `Tested up to:` in `readme.txt` whenever a major WordPress or WooCommerce version drops - check current value before assuming it needs a bump.
- **Dompdf Safeguards:** Any feature introducing images (thumbnails, logos) or custom fonts must pass memory-limit checks on 128M environments before merging.
- **Free/Pro architecture discipline:** New document types default to a free-tier base class; Pro-only logic is added via subclass/decorator, not by writing the free tier out of the class hierarchy (see R4).
- **No Pro version bumps or Pro changelog entries in this document's scope** - Pro work is tracked separately per Section 5 and does not get a release date here.

---

## 5. Background Track: Pro Development (unscheduled, no launch commitment)

These continue at whatever pace is convenient alongside the free-tier releases above, without a version number, release date, or marketing commitment. Revisit scheduling an actual Pro launch at R13 (2027-03-29):

- Finish the Proforma advanced logic split from R4 (independent counters, auto-conversion-on-payment, custom prefixes/suffixes) on top of the free base class.
- Complete and QA the existing Credit Notes scaffold (`class-wcpdf-creditnote.php`) - reverse tax line calculations.
- Independent sequential numbering per document type (separate counters/prefixes/padding).
- Filtered bulk ZIP/merge export (date range, order status, user role, payment gateway) + advanced CSV export filters.
- Multi-recipient supplier automation (warehouse documents emailed by order category/vendor tag).
- Static attachment suite (unlimited attachments mapped to order statuses).
- Licensing system (activation/deactivation/update delivery) - not urgent without a launch date, but worth having ready before one is picked.
- Multilingual document rendering (WPML, Polylang, TranslatePress).
- Automated cloud storage sync (Dropbox, SFTP/FTP).
- Visual table column customizer (show/hide, rename, resize; later extend to packing slip/delivery note).
- Drag-and-drop totals reorder + EU VAT number display.
