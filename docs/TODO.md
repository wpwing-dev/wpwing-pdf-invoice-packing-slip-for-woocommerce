# Implementation TODO

Prioritized backlog derived from the 2026-08-22 competitor analysis
([COMPETITORS.md](COMPETITORS.md)) and the stalled items in
[UPDATE-PLAN.md](UPDATE-PLAN.md). Check items off as they ship; re-prioritize
monthly alongside the competitor snapshot refresh.

Status at writing: shipped version is 1.12.0 (2026-07-19). The weekly cadence
stalled - 1.13.0 (planned 07-26) and 1.14.0 (planned 08-02) have not shipped.

---

## P0 - Unblock and catch up (this week)

- [ ] **Resume the weekly release cadence.** A month of silence is the exact
      pattern Challan is being punished for. Freshness is a ranking factor and
      the cheapest one we control.
- [ ] **Bump compatibility declarations**: Tested up to WP 7.1, note WooCommerce
      11.0 compatibility. WP Overnight and WebToffee already declare both; the
      directory demotes and warns on stale tested-up-to values.
- [ ] **Ship 1.13.0 - support-load reducers** (overdue from UPDATE-PLAN):
  - [ ] System status tab: PHP/WP/WC/Dompdf versions, temp folder writability,
        font cache status, active document types, one-click "Copy system info".
  - [ ] Settings search: client-side filter hiding non-matching rows.
- [ ] **Ship 1.14.0 - setup wizard** (overdue from UPDATE-PLAN): first-run wizard
      (shop info, logo, paper size, auto-generate statuses, email attachment),
      skippable, re-launchable, finish screen linking to docs and the review page.

## P1 - Free-tier parity gaps (next 4-6 weeks)

The free document lineup bar is now invoice + packing slip + delivery note +
shipping label (WebToffee, Challan, add-ons.org). Tyche adds receipts and credit
notes free. We ship 3 of the 4 baseline types.

- [ ] **Shipping Labels document type** - last public readme roadmap promise;
      free at WebToffee, Challan, and add-ons.org. Price-free layout, shipping
      address prominent, optional barcode/QR reuse from the existing QR engine.
- [ ] **Resolve the Proforma promise conflict**: the public readme roadmap
      promises Proforma "in future releases", but the current build has proforma
      in `src/pro/`. Either ship a basic proforma free and gate extras (custom
      numbering sequence, statuses) to Pro, or reword the readme roadmap before
      Pro launches. A broken public promise invites 1-star reviews at exactly
      the moment we start asking for reviews.
- [ ] **Product thumbnails in invoice** (D.1) - free; image support is a selling
      point for add-ons.org, Challan, and now Tyche.
- [ ] **Custom PDF filename patterns** - WebToffee ships this free; small build
      (tokens like {order_number}, {invoice_number}, {date}).
- [ ] **Ink-saving mode** (A.4) - on-demand print-friendly PDF, backgrounds
      stripped. No competitor markets this; cheap differentiator.
- [ ] **WooCommerce Analytics invoice column** (B.3).

## P2 - Pro build-out (aligned with UPDATE-PLAN months 3-4)

Already in progress in `src/pro/`: proforma, credit notes.

- [ ] **Bulk export with filters** (C.3): date range/status/customer modal on the
      existing ZIP/merged-PDF engine. (WebToffee gates filtered bulk export to
      Pro too - converts well.)
- [ ] **Invoice summary CSV export** (C.4): Invoice #, Date, Order #, Customer,
      Total, Tax.
- [ ] **Receipt document type** (C.1). Note: Tyche gives receipts free - keep
      the basic receipt cheap-feeling in Pro or consider shipping it free and
      gating styling; decide when pricing is set.
- [ ] **Separate number sequences per document type** (C.5).
- [ ] **Custom document titles/filenames per type** (C.6).
- [ ] **Supplier/warehouse notification emails** (C.2).
- [ ] **Static file attachments** (C.7): terms/return policy PDFs on chosen emails.
- [ ] **Pay Now link on invoices** - NEW candidate, seen at WebToffee Pro. Deep
      link to WooCommerce order-pay URL for pending orders. Small build, clear
      merchant value, good Pro bullet.
- [ ] Licensing + update delivery, wpwing.com sales page, non-nagging upgrade
      touchpoints in free.

## P3 - Differentiators (Q4 2026, per UPDATE-PLAN months 5-6)

- [ ] **Multilingual PDFs** (D.3): WPML/Polylang first, then Weglot.
- [ ] **Cloud storage upload** (D.4): Dropbox first (every competitor Pro has it),
      then FTP/SFTP.
- [ ] **Drag-and-drop column customizer** (D.2): the feature WP Overnight charges
      most for and add-ons.org wins installs with. Basic version (column pick +
      reorder) before year end.
- [ ] **PDF retention/expiration setting** - NEW candidate from Tyche: auto-delete
      stored PDFs after a configurable period. Storage hygiene + GDPR angle;
      probably free.

## Watching, not building

- **UBL/XML e-invoicing**: now free at both WP Overnight and WebToffee. Still
  Phase E (standalone plugin candidate), but two majors shipping it free means
  re-evaluate at each monthly review rather than annually.
- **PrintNode / remote auto-printing** (WebToffee Pro, WP Overnight add-on):
  note demand signals in support threads before committing.
- **Picklists / address labels** (WebToffee Pro): adjacent document types to
  consider once shipping labels ship.
- **add-ons.org release cadence** and their free drag-and-drop builder.

## Pricing decision (blocks Pro launch)

Ladder as of 2026-08-22: Challan ~$29 -> **us $39-49 (proposed)** -> WebToffee
$69 -> WooCommerce.com $79 -> WP Overnight EUR 99. The $39-49 single-site lane
is still open. Mirror WebToffee's tier structure (1 / 5 / 25 sites) - e.g.
$49 / $89 / $169. Confirm YITH and add-ons.org pricing first (COMPETITORS.md
verify list).
