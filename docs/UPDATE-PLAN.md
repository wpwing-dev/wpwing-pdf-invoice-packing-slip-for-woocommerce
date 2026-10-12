# Product Release Roadmap: Sept 2026 - May 2027 (Q3 2026 + Q4 2026 + Q1/Q2 2027)

**Target Plugin:** PDF Invoice and Packing Slip for WooCommerce (`wpwing-pdf-invoice-packing-slip-for-woocommerce`)
**Current Baseline:** v1.15.0 shipped 07-09-2026 (Shipping Label document type)
**Release Cadence:** Biweekly (every 2 weeks) through December 2026, tapering to monthly January - May 2027. Retimed runway: 07-09-2026 to 03-05-2027.
**Core Objective:** Free Growth & Directory Health only, this cycle. Close remaining free-tier document parity gaps against WP Overnight, WebToffee, and Challan - e-invoicing is the largest of those gaps. Maintain current WordPress/WooCommerce compatibility declarations; deliver on the public readme.txt roadmap commitment to protect 5-star ratings.

**No Pro launch this cycle.** Pro development (Proforma advanced logic, Credit Notes, licensing, filtered export, cloud sync, multilingual, column customizer) continues as an unscheduled background track - see Section 5. There is no v2.0.0 milestone, no BFCM-anchored launch, and no commercial campaign date committed in this document.

---

## 1. Strategic Principles

- **E-invoicing is the real parity gap:** WP Overnight's *free* plugin (300k+ installs) already ships UBL 2.1, Peppol BIS 3.0, CII D16B, Factur-X 1.0, and ZUGFeRD 1.0/2.0. WebToffee's free plugin ships UBL/XML. France requires every business to *receive* e-invoices from 01-09-2026 and French SMEs to *issue* from September 2027, four months past this runway; Germany has required reception since 2025. Structured-format export is therefore the highest-value free-tier work in this cycle, and the feature most likely to anchor a future Pro tier.
- **The Proforma Solution:** The public `readme.txt` roadmap section promises "Proforma Invoice generation" with no free/pro split stated. Gating Proforma entirely behind Pro (its current state, `src/pro/includes/class-wcpdf-proforma.php`) risks community backlash - R4 ships a free-tier Proforma regardless of Pro launch timing.
- **Directory Freshness:** Regular releases signal an actively maintained plugin, but cadence must not outpace real QA capacity - biweekly with a docker `verify` pass and a green test suite per release, tapering to monthly in 2027.
- **BFCM timing risk accepted:** Black Friday (27-11-2026) / Cyber Monday (30-11-2026) drive a real order-volume spike for every store running the *free* plugin. Retiming the whole release train moves R6 to 30-11-2026, so its hardening will not protect stores during Black Friday and may land on Cyber Monday. Do not describe it as pre-spike hardening or compress QA to recover the lost time; prioritize the memory guardrail and reassess release readiness.
- **Pricing Ladder (reference only, not committed to this cycle):** Verified competitor positions as of September 2026 - Challan Pro $49/yr (entry tier $29, lifetime $196), WebToffee $69/yr, WooCommerce.com $79/yr, WP Overnight €69/yr single site (€139/3 sites, €279/25 sites). The earlier $49/$89/$169 ladder in this document assumed a WP Overnight ceiling of €99 and would land a single-site license exactly on Challan Pro rather than between the tiers. Re-derive the ladder when a Pro launch is actually scheduled; do not ship the old numbers.

---

## 2. Release Overview (12 releases, free-tier only)

| # | Date (Mon) | Target Version | Primary Scope & Headline Feature |
| :-- | :--- | :--- | :--- |
| R1 | 07-09-2026 | v1.15.0 | **Shipped.** Shipping Label document type |
| R2 | 21-09-2026 | v1.16.0 | Item tables (thumbnails, SKU/Weight) + migration/email attachment safety fixes |
| R3 | 19-10-2026 | v1.17.0 | Dynamic filename tokens + order column + guest access & hook isolation |
| R4 | 02-11-2026 | v1.18.0 | Free Proforma Invoice + free/Pro proforma architecture split |
| R5 | 16-11-2026 | v1.19.0 | **E-invoicing phase 1:** free UBL 2.1 export + Factur-X feasibility spike |
| R6 | 30-11-2026 | v1.20.0 | Throughput & merge memory bounds + PDF retention/GDPR hygiene |
| R7 | 14-12-2026 | v1.20.1 | **Patch window** - no scheduled feature scope, post-BFCM support cover |
| R8 | 11-01-2027 | v1.21.0 | "Pay Now" direct payment link + static file attachment (1) |
| R9 | 08-02-2027 | v1.22.0 | **E-invoicing phase 2:** Factur-X / ZUGFeRD embed (gated on the R5 spike) |
| R10 | 08-03-2027 | v1.23.0 | PHP floor bump + PHP 8.4 / WP 7.2 readiness + `.pot` refresh |
| R11 | 05-04-2027 | v1.24.0 | Eco ink-saving mode + Invoice Summary CSV export |
| R12 | 03-05-2027 | v1.25.0 | Year-in-review polish + publish Q2 2027 roadmap (Pro launch timing decided here) |

No release is scheduled for 28-12-2026 - holiday freeze week.

**Slack warning:** R7 is the only pure buffer slot left in the cycle. The retimed R6 no longer precedes Black Friday, and the December holiday freeze is retained. If two features slip, move the later dates; do not absorb slippage by cutting the `verify` pass.

---

## 3. Detailed Implementation Plan

### September - November 2026

#### R1 - 07-09-2026 - v1.15.0: Shipping Label Document Type - SHIPPED
- Delivered in [`src/includes/class-wpwing-wcpdf-shipping-label.php`](../src/includes/class-wpwing-wcpdf-shipping-label.php) (`WPWing_WcPdf_Shipping_Label extends WPWing_WcPdf_Document`), wired into the same 6 places `delivery` touches: `class-wpwing-wcpdf-plugin.php`, `class-wpwing-wcpdf-admin.php`, `class-wpwing-wcpdf-orders-list.php`, `class-wpwing-wcpdf-settings.php`, `class-wpwing-wcpdf-wc-hooks.php`, plus `src/templates/default/shipping_label/` and `src/templates/modern/shipping_label/`.
- Note the template directory convention is `shipping_label` (underscore), matching the document type key. Follow it for new document types.
- Shipped without a bug-fix or hardening entry in the changelog, against the Section 4 rule. Hold the next release to it.

#### R2 - 21-09-2026 - v1.16.0: Item Table Rendering & Safety Hardening
- Setting toggle to render product featured-image thumbnails inside Invoices, Packing Slips, Delivery Notes, and Shipping Labels.
- Automatic image downscaling and dimension caching to prevent Dompdf memory exhaustion - test on a 128M PHP `memory_limit` environment.
- Dimension selector (32x32, 48x48, 64x64).
- Column visibility checkboxes for SKU and Weight. Bundled here because both features edit the same item-table template partials; splitting them means touching that code twice.
- **Hardening / Bug Fixes (satisfies Section 4 Changelog Rule):**
  - **Decouple migration versioning (P0):** Replace `WPWING_WCPDF_VERSION` in `WPWing_WcPdf_Migration::maybe_run()` with an independent `DB_VERSION = 1` constant. Fixes unindexed table-wide `UPDATE` queries on `wp_postmeta` and `wc_orders_meta` firing on every minor plugin update.
  - **Respect free-order email setting (P1):** Ensure `WPWing_WcPdf_Wc_Hooks::attach_document_to_email()` checks `invoice_disable_free_orders` before generating and attaching an invoice to transactional emails.
  - **Advisory lock safety (P2):** Wrap invoice counter increment in `WPWing_WcPdf_Invoice::get_new_invoice_number()` in `try { ... } finally { $wpdb->query( ... RELEASE_LOCK ... ); }` to prevent deadlock if option saving fails.
  - **CI suite expansion:** Add `phpunit`, `phpstan`, and frontend linters to `.github/workflows/ci.yml`.

#### R3 - 19-10-2026 - v1.17.0: Dynamic Filename Patterns & Order Column
- The core feature and hardening changes are implemented in the branch. Full test-suite and end-to-end verification remain required before release.
- Token-based filename formatting setting supporting `{{doc_type}}`, `{{order_number}}`, `{{invoice_date}}`.
- Invoice Number column on the standard WooCommerce orders list, via the existing `class-wpwing-wcpdf-orders-list.php`.
- **Not** the WooCommerce Analytics report column from the previous revision. Analytics is a React/`@wordpress/data` surface and this repo's asset pipeline is sass + terser only (`build:css`, `build:js` in `package.json`) with no `@wordpress/scripts`, JSX, or bundler. That item is a toolchain adoption, not a half-release feature; revisit it as its own release if demand justifies the build-system cost.
- **Hardening / Bug Fixes (satisfies Section 4 Changelog Rule):**
  - **Safe template hook cleanup (P1):** Refactor `WPWing_WcPdf_Document::flush_template()` to unhook only internal plugin callback instances instead of `remove_all_filters()`, preventing the erasure of third-party custom hooks during batch generation.
  - **Guest order authorization (P2):** Add WooCommerce `order_key` verification to `user_can_manage_order()` and `WPWing_WcPdf_Shortcodes` so guest buyers can download invoices on the Order Received (Thank You) page.

#### R4 - 02-11-2026 - v1.18.0: Free Proforma Invoice + Architecture Split
- **Architecture work first:** refactor `WPWing_WcPdf_Proforma` (currently `src/pro/includes/class-wcpdf-proforma.php`, extending `WPWing_WcPdf_ProDocument`) so a free-tier base class extending `WPWing_WcPdf_Document` - mirroring `WPWing_WcPdf_Delivery` - carries standard order data and standard sequential numbering, and the existing Pro class becomes a subclass/decorator adding independent counters, auto-conversion-on-payment, and custom prefixes/suffixes. Confirm the Pro plugin's activation check falls back to the free base class when Pro is inactive.
- Free: Proforma selectable for "Pending payment" / "On-hold" orders, prominent "PROFORMA INVOICE" header/watermark.
- Removes the last item from the public `readme.txt` roadmap section - update that section in the same release.

---

### November 2026 - January 2027

#### R5 - 16-11-2026 - v1.19.0: E-invoicing Phase 1 (UBL 2.1)
- Free UBL 2.1 XML export for invoices: download from the order metabox, bulk export from the orders list, and an optional attach-to-email toggle alongside the existing PDF attachment.
- Map WooCommerce order, tax, and seller/buyer data to UBL 2.1 fields. Seller VAT/tax identifiers need new settings fields - reuse `class-wpwing-wcpdf-settings-api.php`.
- **Timeboxed spike, same release, no shipped feature:** can Dompdf 2.0.4 embed a PDF/A-3 attachment (the Factur-X requirement) at all, or does it need a post-processor such as FPDI or a second library? Record the answer in this document. R9 is gated on it, and the answer decides whether R9 ships an embed or a plain XML sidecar.
- Ship UBL first even if the spike comes back red - XML export alone closes most of the WebToffee/Challan free-tier gap.

#### R6 - 30-11-2026 - v1.20.0: Throughput Hardening + Storage Hygiene
- Throughput hardening for simultaneous PDF generation and email-attachment load - profile bulk ZIP/merge on a several-hundred-order batch, cap peak memory, and confirm no fatal on a 128M `memory_limit`. This retimed release is not ahead of Black Friday; prioritize the memory guardrail and do not skip QA.
- **Bulk merged PDF memory bounds (P1):** Add order-count guardrails in `WPWing_WcPdf_Orders_List::stream_merged_pdf()` (cap at 30 orders per merged PDF with notice to use ZIP for larger sets) to prevent Dompdf memory exhaustion crashes on 128M hosts.
- PDF storage retention scheduler (purge cached temp PDFs after 30/60/90 days) plus a disk/temp-dir size check in System Status.

#### R7 - 14-12-2026 - v1.20.1: Post-BFCM Patch Window
- No feature scope, and no release at all unless something breaks. Review support reports from the Black Friday/Cyber Monday spike and patch only.

#### R8 - 11-01-2027 - v1.21.0: Pay Now + Static Attachment
- "Pay Now" link on unpaid Invoices and Proformas, routed to native `order-pay`.
  - **Security constraint, decide before building:** the `order-pay` URL carries `key=wc_order_*`, which is a bearer credential for that order. A PDF is emailed, archived, and forwarded, so embedding that key makes it long-lived and shareable. Render the link only on unpaid documents, never on paid or archived ones, and decide the expiry/regeneration behavior up front.
  - Deliberately placed after the BFCM peak rather than before it - this touches money-adjacent flows.
- Support attaching 1 static PDF (e.g. Terms of Service) to completed-order emails.

---

### February - May 2027 (monthly cadence)

#### R9 - 08-02-2027 - v1.22.0: E-invoicing Phase 2 (Factur-X / ZUGFeRD)
- Gated on the R5 spike. If PDF/A-3 embedding is viable, ship Factur-X 1.0 / ZUGFeRD 2.0 hybrid invoices: the existing PDF with the structured XML embedded.
- If the spike came back red, this slot ships the XML sidecar workflow plus Peppol BIS 3.0 mapping instead, and the embed moves to the Q2 2027 roadmap.

#### R10 - 08-03-2027 - v1.23.0: PHP Floor & Compatibility Pass
- Raise the `Requires PHP` floor. The plugin currently declares 7.4, which is long past EOL, and WP Overnight's v6 moves to 8.1+. Decide the new floor (8.0 or 8.1) and update both the plugin header and `readme.txt`.
- Full compatibility pass for PHP 8.4 and WordPress 7.2 alpha/beta builds.
- Deprecated hook cleanup, sequential-numbering index optimization on large databases.
- Refresh base `.pot` translation files (`make pot`).

#### R11 - 05-04-2027 - v1.24.0: Eco Mode + CSV Export
- Eco ink-saving mode: 1-click toggle stripping solid backgrounds/dark headers/heavy borders to thin line-art; "Print Eco PDF" action in the order metabox.
- Invoice Summary CSV export (Invoice #, Date, Order #, Customer, Subtotal, Tax, Grand Total) - current page/standard batch.
- Both were displaced from R5 by the e-invoicing work. If the schedule has slipped by this point, these are the items to cut, not the compatibility pass.

#### R12 - 03-05-2027 - v1.25.0: Year-in-Review & Q2 2027 Roadmap
- Final polish pass across the cycle's features.
- Publish a "Year in Review & Q2 2027 Roadmap" post.
- **This is the checkpoint to decide Pro launch timing**, informed by: how much of the Section 5 background track is actually done, real release velocity from R1-R11, free-tier install/rating trends, and whether e-invoicing landed well enough to anchor a paid tier.

---

## 4. Operational Checklist for AI Agents & Developers

- **Reality-check the plan first:** Before starting a release, confirm this document's assumed starting state against the actual code. This plan has drifted from reality three times - each revision has listed already-shipped work as pending. Check the plugin header version, `readme.txt` changelog, and the relevant class before trusting any "not built yet" claim here.
- **Cadence:** Biweekly Monday releases through 14-12-2026, then monthly January - May 2027. No release is scheduled during the 28-12-2026 holiday freeze. The retimed R6 is not pre-BFCM hardening; do not compress releases or cut verification to imply otherwise.
- **Verify before ship:** Run `make test-unit`, `make test-e2e`, and the `verify` skill (build + launch in the docker stack, exercise the feature end-to-end) before tagging any release. `make lint-all` and `make phpstan` must be clean.
- **Tag every release:** the repo currently has zero git tags despite 15 shipped versions, which also leaves `make changelog` (commits since last tag) with nothing to diff against. Establish the baseline tag (`git tag -a v1.15.0 -m "Release v1.15.0"`) immediately, and run `make tag V=x.y.z` and `make dist` as part of every release.
- **Changelog Rule:** Every release must contain at least one bug fix or hardening item alongside the headline feature, except R7, whose entire scope *is* fixes/hardening. v1.15.0 shipped without one - do not let that become the norm.
- **Compatibility Watch:** Update `Tested up to:` in `readme.txt` whenever a major WordPress or WooCommerce version drops - check the current value before assuming it needs a bump. Both the plugin header and `readme.txt` currently declare WP 7.1 / WC 11.0.1.
- **Dompdf Safeguards:** Any feature introducing images (thumbnails, logos) or custom fonts must pass memory-limit checks on 128M environments before merging.
- **Free/Pro architecture discipline:** New document types default to a free-tier base class; Pro-only logic is added via subclass/decorator, not by writing the free tier out of the class hierarchy (see R4).
- **Paths:** the plugin root is `src/`, not the repo root. Template directories use the underscore document key (`shipping_label`, not `shipping-label`).
- **No Pro version bumps or Pro changelog entries in this document's scope** - Pro work is tracked separately per Section 5 and does not get a release date here.

---

## 5. Background Track: Pro Development (unscheduled, no launch commitment)

These continue at whatever pace is convenient alongside the free-tier releases above, without a version number, release date, or marketing commitment. Revisit scheduling an actual Pro launch at R12 (03-05-2027):

- Finish the Proforma advanced logic split from R4 (independent counters, auto-conversion-on-payment, custom prefixes/suffixes) on top of the free base class.
- Complete and QA the existing Credit Notes scaffold (`class-wcpdf-creditnote.php`) - reverse tax line calculations.
- Advanced e-invoicing on top of the free UBL/Factur-X base: Peppol network delivery, per-country profiles, validation/error reporting. This is the strongest Pro anchor candidate, since the free tier only needs to produce a valid file.
- Independent sequential numbering per document type (separate counters/prefixes/padding).
- Filtered bulk ZIP/merge export (date range, order status, user role, payment gateway) + advanced CSV export filters.
- Multi-recipient supplier automation (warehouse documents emailed by order category/vendor tag).
- Static attachment suite (unlimited attachments mapped to order statuses).
- Licensing system (activation/deactivation/update delivery) - not urgent without a launch date, but worth having ready before one is picked.
- Multilingual document rendering (WPML, Polylang, TranslatePress).
- Automated cloud storage sync (Dropbox, SFTP/FTP).
- Visual table column customizer (show/hide, rename, resize; later extend to packing slip/delivery note).
- Drag-and-drop totals reorder + EU VAT number display.
