# Product Release Roadmap & Marketing Plan: Q3/Q4 2026

**Target Plugin:** PDF Invoice and Packing Slip for WooCommerce (`wpwing-pdf-invoice-packing-slip-for-woocommerce`)  
**Current Baseline:** v1.14.0 shipped  
**Release Cadence:** Weekly every **Monday** (17-week runway: 2026-09-07 to 2026-12-28)[cite: 1]  
**Dual Core Objectives:**
1. **Free Growth & Directory Health:** Close remaining document parity gaps against WebToffee, Challan, and Tyche[cite: 2]; maintain WordPress 7.1 / WooCommerce 11.0 compatibility declarations[cite: 1, 2]; deliver on public readme commitments to protect 5-star ratings[cite: 1, 2].
2. **Commercial Monetization:** Resolve the Proforma delivery roadmap[cite: 2], package high-converting commercial triggers (filtered bulk export, custom numbering sequences, static attachments, cloud sync)[cite: 1, 2], and execute a commercial **Pro Launch (v2.0)** ahead of **Black Friday / Cyber Monday (BFCM)**.

---

## 1. Strategic Principles

- **The Proforma Solution:** The public `readme.txt` promised Proforma in future releases[cite: 2]. Gating the entire document behind Pro risks community backlash[cite: 2].
  - **Free Tier:** Ships a clean, basic Proforma document type (standard order data, standard sequential numbering).
  - **Pro Tier:** Gates advanced business logic (independent counter sequences, automated conversion from Proforma to Tax Invoice on payment, and custom document prefixes/suffixes)[cite: 1, 2].
- **Directory Freshness:** Regular Monday releases signal an actively maintained, reliable plugin on WordPress.org[cite: 1, 2].
- **Pricing Ladder:** Positioned between Challan (~$29) and WebToffee ($69) / WooCommerce.com ($79) / WP Overnight (€99)[cite: 1, 2]:
  - Single Site: $49 / year[cite: 2]
  - 5 Sites (Agency Starter): $89 / year[cite: 2]
  - 25 Sites (Agency Unlimited): $169 / year[cite: 2]

---

## 2. 17-Week Sprint Overview

| Week | Date (Mon) | Target Version | Primary Scope & Headline Feature | Target Tier |
| :--- | :--- | :--- | :--- | :--- |
| W01 | 2026-09-07 | v1.15.0 | Shipping Labels Document Type & WP 7.1 Compatibility[cite: 1, 2] | Free |
| W02 | 2026-09-14 | v1.16.0 | Product Thumbnails in Item Tables & Dompdf Scaling[cite: 1, 2] | Free |
| W03 | 2026-09-21 | v1.17.0 | Dynamic Filename Token Builder & WC Analytics Column[cite: 1, 2] | Free |
| W04 | 2026-09-28 | v1.18.0 | Standard Proforma Invoice (Roadmap Promise Delivery)[cite: 2] | Free |
| W05 | 2026-10-05 | v1.19.0 | Eco Ink-Saving Mode (Background & Heavy Asset Stripper)[cite: 1, 2] | Free |
| W06 | 2026-10-12 | v1.20.0 | Invoice Summary CSV Export (Accounting Data Bridge)[cite: 1, 2] | Free / Pro Prep |
| W07 | 2026-10-19 | v1.21.0 | "Pay Now" Direct Payment Link on Unpaid Invoices[cite: 2] | Free |
| W08 | 2026-10-26 | v1.22.0 | PDF Storage Retention Scheduler & GDPR Hygiene[cite: 2] | Free |
| W09 | 2026-11-02 | v1.23.0 | Static File Attachments & Warehouse Notifications Engine[cite: 1, 2] | Free / Pro Prep |
| W10 | 2026-11-09 | v1.24.0 / v2.0-RC | Pro Release Candidate Feature Freeze & Licensing Audit[cite: 1, 2] | Internal / Pre-launch |
| W11 | 2026-11-16 | **v2.0.0 PRO** | **COMMERCIAL PRO LAUNCH & BFCM CAMPAIGN KICKOFF** | **Pro Major** |
| W12 | 2026-11-23 | v2.0.1 / v1.25.1 | BFCM Peak Support SLA & High-Volume Store Hardening | Hotfix & SLA |
| W13 | 2026-11-30 | v2.0.2 / v1.26.0 | Multilingual PDF Generation (WPML, Polylang, TranslatePress)[cite: 1, 2] | Pro |
| W14 | 2026-12-07 | v2.1.0 / v1.27.0 | Automated Cloud Storage Sync (Dropbox & SFTP/FTP)[cite: 1, 2] | Pro |
| W15 | 2026-12-14 | v2.2.0 / v1.28.0 | Visual Table Column Customizer (Show/Hide, Widths, Order)[cite: 1, 2] | Pro |
| W16 | 2026-12-21 | v2.2.1 / v1.28.1 | Drag-and-Drop Totals Reorder & EU VAT Integration[cite: 1] | Pro |
| W17 | 2026-12-28 | v2.3.0 / v1.29.0 | PHP 8.4 & WP 7.2 Readiness Pass + Year-End Review[cite: 1] | Free & Pro |

---

## 3. Detailed Weekly Implementation Plan

### Month 1: September 2026 — Document Parity & Visual Upgrades

#### Week 1: 2026-09-07 — v1.15.0: Shipping Labels Document Type
- **Headline Feature:** Shipping Labels document type[cite: 1, 2].
- **Free Changes:**
  - Introduce `Shipping Label` class extending base document architecture[cite: 1].
  - Clean, price-free layout focusing on sender details, prominent recipient shipping address, order weight, and barcode/QR code integration[cite: 1, 2].
  - Orders list bulk actions: Bulk print / Merge Shipping Labels into a single PDF[cite: 1].
  - Ensure `Tested up to: 7.1` and WooCommerce 11.0 compatibility tags are set in `readme.txt`[cite: 1, 2].
- **Impact:** Delivers on the final open document promise from the public roadmap, closing parity with WebToffee, Challan, and add-ons.org[cite: 1, 2].

#### Week 2: 2026-09-14 — v1.16.0: Product Thumbnails in Line Items
- **Headline Feature:** Product Thumbnails in item tables[cite: 1, 2].
- **Free Changes:**
  - Setting toggle to render product featured image thumbnails inside Invoices, Packing Slips, and Delivery Notes[cite: 1, 2].
  - Automatic image downscaling and dimension caching to prevent Dompdf memory exhaustion on high-resolution merchant assets.
  - Dimension selector (32x32, 48x48, 64x64).
- **Impact:** Visual picking aid for warehouse operations, matching a staple feature across add-ons.org, Challan, and Tyche[cite: 1, 2].

#### Week 3: 2026-09-21 — v1.17.0: Dynamic Filename Patterns & Analytics
- **Headline Feature:** Custom PDF Filename Patterns + WC Analytics[cite: 1, 2].
- **Free Changes:**
  - Token-based filename formatting setting supporting tags such as `{{doc_type}}`, `{{order_number}}`, `{{invoice_date}}`[cite: 2].
  - Add dedicated "Invoice Number" column to WooCommerce Analytics Orders report with quick-view modal[cite: 1, 2].
- **Impact:** Improves file management for store owners, matching WebToffee free-tier functionality[cite: 2].

#### Week 4: 2026-09-28 — v1.18.0: Standard Proforma Invoice (Free Delivery)
- **Headline Feature:** Free Proforma Invoice document type[cite: 2].
- **Free Changes:**
  - Standard Proforma document type selectable for "Pending payment" and "On-hold" orders[cite: 2].
  - Standard styling with prominent "PROFORMA INVOICE" header and watermark.
  - Teaser triggers displaying inactive toggles for Pro capabilities (e.g., independent sequential numbering, auto-conversion to Tax Invoice upon order completion)[cite: 1, 2].
- **Impact:** Fulfills the public roadmap promise in `readme.txt` without gating core functionality[cite: 2].

---

### Month 2: October 2026 — Differentiators & Commercial Packaging

#### Week 5: 2026-10-05 — v1.19.0: Eco Ink-Saving Mode
- **Headline Feature:** Ink-saving print mode[cite: 1, 2].
- **Free Changes:**
  - 1-click toggle to strip solid background colors, dark block headers, and heavy borders, replacing them with thin line-art styling[cite: 1, 2].
  - On-demand "Print Eco PDF" action in the admin order metabox[cite: 1, 2].
- **Impact:** Unique cost-saving differentiator not actively marketed by primary competitors[cite: 1, 2].

#### Week 6: 2026-10-12 — v1.20.0: Invoice Summary CSV Export
- **Headline Feature:** Accounting CSV export[cite: 1, 2].
- **Free / Pro Changes:**
  - Orders list export action generating an accounting summary CSV: Invoice #, Date, Order #, Customer, Subtotal, Tax Total, Grand Total[cite: 1, 2].
  - Free tier: Export current page / standard batch[cite: 1].
  - Pro tier (seeded): Unlocked date range and status filters[cite: 1, 2].
- **Impact:** Solves a major operational pain point for store accountants and bookkeepers[cite: 1, 2].

#### Week 7: 2026-10-19 — v1.21.0: "Pay Now" Direct Payment Link
- **Headline Feature:** Deep-linked invoice payments[cite: 2].
- **Free Changes:**
  - Render an optional "Pay Now" link/button on unpaid Invoices and Proformas[cite: 2].
  - Routes directly to WooCommerce native `order-pay` URL[cite: 2].
- **Impact:** Increases cash collection speed for B2B stores issuing invoices before payment[cite: 2].

#### Week 8: 2026-10-26 — v1.22.0: PDF Storage Hygiene & GDPR Retention
- **Headline Feature:** Automated storage retention scheduler[cite: 2].
- **Free Changes:**
  - Configurable retention schedule to automatically purge cached temporary PDF files after 30, 60, or 90 days[cite: 2].
  - Disk storage health and temp directory size check inside the System Status tab[cite: 1, 2].
- **Impact:** Prevents server disk bloat and satisfies GDPR data minimization standards[cite: 2].

---

### Month 3: November 2026 — Pro Launch & Black Friday / Cyber Monday Surge

#### Week 9: 2026-11-02 — v1.23.0: Static Attachments & Supplier Engine
- **Headline Feature:** Static file attachments & vendor notification hooks[cite: 1, 2].
- **Free Changes:**
  - Support attaching 1 static PDF (e.g., standard Terms of Service) to customer completed order emails[cite: 1, 2].
- **Pro Staging:**
  - Automated supplier notification engine routing warehouse packing slips or delivery notes directly to vendor/warehouse email addresses upon order status change[cite: 1, 2].

#### Week 10: 2026-11-09 — v1.24.0 / v2.0.0-RC: Pro Release Candidate Freeze
- **Scope:**
  - Feature freeze for Pro v2.0.0.
  - Licensing verification (activation, deactivation, update delivery checks)[cite: 1, 2].
  - Security audit: strict capability checks and nonce validation on all document endpoints.
  - Send launch teaser email to user base announcing upcoming BFCM availability.

#### Week 11: 2026-11-16 — MAJOR RELEASE: Wpwing PDF Invoice PRO v2.0.0
- **Commercial Launch Features (Pro v2.0.0):**
  1. **Independent Sequential Numbering:** Separate counters, prefixes, and padding per document type (Invoices, Proformas, Credit Notes, Delivery Notes, Shipping Labels)[cite: 1, 2].
  2. **Filtered Bulk ZIP/Merge Engine:** Export PDFs filtered by date range, order status, user role, and payment gateway[cite: 1, 2].
  3. **Credit Notes:** Automated refund document generation with reverse tax line calculations[cite: 2].
  4. **Multi-Recipient Supplier Automation:** Email warehouse documents automatically based on order category or vendor tag[cite: 1, 2].
  5. **Static Attachments Suite:** Unlimited static PDF attachments mapped to specific order statuses[cite: 1, 2].
- **Free Plugin (v1.25.0):** Native license key activation interface without aggressive nagging[cite: 1, 2].
- **Marketing Campaign:** Black Friday Early-Bird Launch: $49/yr or $99 limited Lifetime License[cite: 2].

#### Week 12: 2026-11-23 — v2.0.1 / v1.25.1: BFCM Peak Support & Hotfix Sprint
- **Scope:**
  - Support SLA priority coverage during Black Friday / Cyber Monday weekend.
  - Performance patches for high-throughput stores generating simultaneous invoice PDFs.
- **Marketing Focus:** "48 Hours Remaining" BFCM campaign push.

#### Week 13: 2026-11-30 — v2.0.2 / v1.26.0: Multilingual Document Generation
- **Headline Feature:** Multilingual order language rendering[cite: 1, 2].
- **Pro Changes:**
  - Integration with WPML, Polylang, and TranslatePress[cite: 1, 2].
  - Renders invoice labels, dates, and currencies in the customer's checkout language rather than the backend admin language[cite: 1].
- **Free Changes:** Refresh base `.pot` translation files.

---

### Month 4: December 2026 — Differentiators & Year-End Polish

#### Week 14: 2026-12-07 — v2.1.0 / v1.27.0: Automated Cloud Storage Sync
- **Headline Feature:** Cloud backup integration[cite: 1, 2].
- **Pro Changes:**
  - Automatic background PDF upload to **Dropbox** and secure **SFTP/FTP** upon invoice creation[cite: 1, 2].
  - Directory structure customization: `/Invoices/YYYY/MM/Invoice-XXXX.pdf`.
- **Impact:** Eliminates manual document archiving for enterprise stores[cite: 1].

#### Week 15: 2026-12-14 — v2.2.0 / v1.28.0: Visual Column Customizer (Phase 1)
- **Headline Feature:** Item table column manager[cite: 1, 2].
- **Pro Changes:**
  - Visual settings interface to show/hide, rename, and adjust column widths for product item tables (SKU, Image, Title, Price, Qty, Tax, Total)[cite: 1, 2].
- **Free Changes:** Add basic column visibility checkboxes for SKU and Weight.
- **Impact:** Directly targets the top commercial feature of WP Overnight and add-ons.org[cite: 1, 2].

#### Week 16: 2026-12-21 — v2.2.1 / v1.28.1: Drag-and-Drop Totals & Tax Compliance
- **Headline Feature:** Order totals sorting and tax layout polish[cite: 1].
- **Pro Changes:**
  - Drag-and-drop order reordering for totals lines: Subtotal, Discount, Shipping, Tax Breakdown, Grand Total.
  - EU VAT number display integration for standard checkout fields.
- **Impact:** Supports complex tax configurations across EU, UK, and US jurisdictions[cite: 1].

#### Week 17: 2026-12-28 — v2.3.0 / v1.29.0: PHP 8.4, WP 7.2 Pass & Year-End Review
- **Headline Feature:** Infrastructure hardening and next-cycle compatibility[cite: 1].
- **Free & Pro Changes:**
  - Full compatibility pass for PHP 8.4 and WordPress 7.2 alpha/beta builds[cite: 1].
  - Deprecated hook cleanups and sequential numbering index optimizations on large databases.
- **Marketing Action:** Publish "2026 Year in Review & 2027 Roadmap" blog post.

---

## 4. Operational Checklist for AI Agents & Developers

- **Strict Monday Release Cadence:** Releases are packaged and tagged every Monday morning[cite: 1].
- **Changelog Rule:** Every release must contain at least one bug fix or hardening item alongside the headline feature[cite: 1].
- **Compatibility Watch:** Ensure `Tested up to:` in `readme.txt` is updated whenever a major WordPress or WooCommerce version drops[cite: 1, 2].
- **Dompdf Safeguards:** Any feature introducing images (thumbnails, logos) or custom fonts must pass memory limit checks on 128M environments before merging.