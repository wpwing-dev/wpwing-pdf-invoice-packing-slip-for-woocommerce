# Weekly Update Plan - H2 2026

Working plan for the weekly release cadence (Sunday releases). Month 1 is planned in
detail; months 2-6 are a directional arc, re-planned monthly against
[COMPETITORS.md](COMPETITORS.md) (re-fetch the live pages each time).

- Current shipped version: 1.12.0 (2026-07-19)
- Feature IDs (A.3, B.1, C.4, ...) refer to `DOC/Future Plan.md`
- Status: A.1, A.2 and most of Phase B-adjacent quick wins through 1.10.0 are done;
  bulk ZIP/merged-PDF download shipped free in 1.10.0 (competitors gate this to Pro);
  1.11.0 (shortcode + invoice search) and 1.12.0 (Delivery Note) shipped on schedule

---

## Month 1 (detailed) - close free-tier gaps, honor public roadmap

### 1.11.0 - 2026-07-12 - Quick wins bundle [SHIPPED]

Two small, self-contained features (A.3 + B.4). Can slip to mid-week without
breaking cadence.

- **Document link shortcode** (A.3): context-aware `[wpwing_invoice]` with no
  required attributes. Resolves the order from the Thank You page or My Account
  order context; renders nothing when no context is found.
- **Search orders by invoice number** (B.4): both classic (`posts_search`/`posts_join`)
  and HPOS (`woocommerce_order_query_args`) paths.
- Housekeeping: close GitHub issues #3 and #4 (shipped in 1.5.0, still open per
  COMPETITORS.md TODO).

### 1.12.0 - 2026-07-19 - Delivery Note document type [SHIPPED]

First of the three public readme roadmap promises (Proforma, Delivery Notes,
Shipping Labels). Challan ships this free - closest-peer parity.

- New free document type `Delivery Note` extending the base document class.
- Templates landed in `templates/default/delivery/` and `templates/modern/delivery/`.
- Metabox buttons, orders-list bulk actions (generate/ZIP/merged PDF),
  auto-generate statuses - same surface as the packing slip.
- No prices shown (like packing slip), optional customer note block.
- Not included: email attachment option (invoice-only today; revisit if requested).

### 1.13.0 - 2026-07-26 - Support-load reducers

B.2 + B.5 together - both live in the settings screen and reduce support burden
ahead of the Pro launch.

- **System status tab** (B.2): PHP/WP/WC/Dompdf versions, temp folder path and
  writability, font cache status, active document types, plugin version. One-click
  "Copy system info" button.
- **Settings search** (B.5): client-side filter input that hides non-matching
  setting rows in real time.

### 1.14.0 - 2026-08-02 - Setup wizard

B.1 - the biggest remaining free-tier polish item, gets a full week. WP Overnight
has one; first-run experience matters for converting new installs into reviews.

- First-run wizard on activation (re-launchable from settings).
- Steps: shop name and address, logo upload, paper size, auto-generate statuses,
  email attachment choice.
- Completion flag in `wp_options`; skippable at every step.
- Finish screen links to docs and review page (installs are our weakest number
  vs. every competitor - reviews drive wordpress.org ranking).

---

## Months 2-6 (directional arc)

### Month 2 (Aug) - finish free parity, prep Pro

- **Shipping Labels** document type (last public readme promise; Challan free parity).
- **Ink-saving mode** (A.4): on-demand "Print-friendly PDF" button, backgrounds stripped.
- **Product thumbnails in invoice** (D.1) - ship free; add-ons.org and Challan
  make image support a selling point.
- **WooCommerce Analytics invoice column** (B.3) + polish/fix release.
- Business: verify YITH and add-ons.org Pro pricing (COMPETITORS.md TODOs), decide
  our Pro price point. Anchors: Challan ~$29/yr low end, WooCommerce.com $79/yr,
  WP Overnight EUR 99/yr bundle. Suggested lane: $39-49/yr.

### Month 3 (Sep) - Pro feature completion

Fastest-revenue items per Future Plan.md:

- **Bulk export with filters** (C.3): date range/status/customer filter modal on top
  of the existing ZIP engine (already shipped free in 1.10.0 - Pro adds the filters).
- **Invoice summary CSV export** (C.4): Invoice #, Date, Order #, Customer, Total, Tax.
- **Receipt document type** (C.1).
- **Separate number sequences per document type** (C.5) + **custom titles/filenames** (C.6).

### Month 4 (Oct) - Pro launch

- **Supplier/warehouse notification emails** (C.2) - per-status recipient +
  attached document.
- **Static file attachments** (C.7) - terms/return policy PDFs on chosen emails.
- Licensing + update delivery infrastructure, wpwing.com sales page, upgrade
  touchpoints in the free plugin (non-nagging).
- Free releases continue as fix/compatibility updates during launch weeks.

### Month 5 (Nov) - Pro differentiators

- **Multilingual PDFs** (D.3): render in the customer's order language
  (WPML/Polylang first, Weglot later).
- **Cloud storage upload** (D.4): Dropbox first (every competitor's Pro has it),
  then FTP/SFTP; Google Drive only if demand shows.

### Month 6 (Dec) - the big differentiator

- **Drag-and-drop column customizer** (D.2): multi-week build, the feature WP
  Overnight charges most for and add-ons.org wins installs with. Ship a basic
  version (column pick + reorder for product table and totals) before year end;
  iterate in Jan.
- Year-end compatibility pass (WP/WC versions, PHP 8.4).

---

## Standing weekly checklist

- Every release: at least one Fix/Improvement line alongside the headline feature
  (the 1.8.x-1.10.0 pattern of hardening releases builds the reputation WP
  Overnight has and WooCommerce.com's official plugin lost).
- Monthly: re-fetch competitor pages, update COMPETITORS.md snapshot, re-plan the
  next month here.
- Watch: WP Overnight's e-invoicing push (we stay out per Phase E - standalone
  plugin candidate), add-ons.org release cadence (drag-and-drop builder in free).
