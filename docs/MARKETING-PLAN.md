# Growth / Marketing Plan

Goal: grow from **10+ active installs** (5.0 rating, 1 review, snapshot
2026-09-21) to a self-sustaining install base of 1,000+ stores that supports the Pro launch.

Written alongside [COMPETITORS.md](COMPETITORS.md) and [UPDATE-PLAN.md](UPDATE-PLAN.md).
Current plugin version: **1.16.0**.

---

## Targets

WordPress.org displays active installs in discrete tiers (10+, 20+, 50+, 100+, 200+, 500+, 1,000+).
Growth compounds gradually at first before directory search network effects take over:

| Milestone | Target Date | Leading Indicators |
|---|---|---|
| 50+ installs, 5 reviews | Oct 2026 | downloads/day > 15, weekly release cadence maintained |
| 100+ installs, 10 reviews | Dec 2026 | first-page directory rank for 1 primary keyword ("delivery note woocommerce") |
| 500+ installs, 25 reviews | Mar 2027 | Pro launched, high-intent comparison content ranking on Google |
| 1,000+ installs | Jun 2027 | Organic directory installs exceed churn consistently |

**Track weekly:** active installs bracket, downloads/day (WordPress.org advanced stats), review count, directory search position for "pdf invoice", "packing slip", "delivery note", "shipping label", and support forum response time.

---

## 1. Competitive Value Matrix

Based on live competitive tracking in [`docs/COMPETITORS.md`](COMPETITORS.md):

| Capability | WP Overnight (300k+) | WebToffee (50k+) | WooCommerce.com ($79/yr) | **WPWing (Ours - Free)** |
|---|---|---|---|---|
| **Free Document Types** | Invoice & Packing Slip only | Invoice, Packing, Delivery, Shipping | Invoice only (Paid) | **All 4 Free:** Invoice, Packing Slip, Delivery Note, Shipping Label |
| **Product Thumbnails in Tables** | Paid add-on (EUR 99/yr) | Paid Pro ($69/yr) | Not included | **100% Free** (v1.16.0) with auto downscaling & local file caching |
| **Bulk Merged PDF & ZIP** | Paid (EUR 99/yr) | Paid Pro ($69/yr) | Not included | **100% Free** directly from Orders list |
| **QR Code on Documents** | Paid add-on | Paid Pro | Not included | **100% Free** (Order URL or custom token string) |
| **Currency Symbol Rendering** | Standard (tofu boxes on Asian currencies) | RTL add-on required | Standard (tofu boxes) | **Native Bundled Currency Font** (Taka, Rupee, Baht, etc. render flawlessly) |
| **Onboarding UX** | Single dense settings tab | Multi-step form | Basic settings | **Interactive 2-min Setup Wizard** + Live Preview Panel in Admin |
| **Code Weight & Speed** | Heavy (enterprise e-invoicing stack) | Medium (multiple external libs) | Basic | **Ultra-lightweight**, Zero tracking, HPOS Native |

### Primary Positioning Angle:
> *"The complete 4-in-1 WooCommerce document suite without the EUR 99 price tag or sluggish server bloat."*

---

## 2. Win the WordPress.org Directory (Directory ASO)

At the 10+ install stage, **over 90% of qualified installs originate directly inside the WordPress.org plugin directory search**. The search algorithm ranks listings based on:
1. Exact keyword matches in title, short description, tags, and section headers.
2. Active install velocity and daily download trajectory.
3. Review count, review frequency, and 5-star rating average.
4. Support thread resolution ratio (publicly shown on directory page).
5. Tested-up-to freshness and recent update recency.

### 2a. Listing Hygiene & Keyword Optimization
- [x] **Bump Tested up to: 7.1** - matching market leaders; avoids stale warning banners.
- [x] **Active release cadence** - v1.16.0 ships fresh updates; cadence is a signal we control.
- [x] **Optimized 5 Tags** - replace duplicate "Packing List" with high-intent keywords:
  `pdf invoice, packing slip, delivery note, shipping label, woocommerce`
- [x] **Short Description (150-char limit)** - rewrite to mention all 4 document types:
  > `Automate PDF invoices, packing slips, delivery notes & shipping labels for WooCommerce. Attach to emails, print in bulk. Fast & lightweight.`
- [x] **Screenshots Suite (8 shots)** - automated via Playwright inside Docker (`make screenshots`), leading with the finished invoice template, delivery notes, shipping labels, live preview panel, and open bulk actions dropdown.

### 2b. The "Live Preview" Playground Blueprint (High Leverage Differentiator)
- [ ] Add a WordPress Playground blueprint (`assets/blueprints/blueprint.json` in SVN).
- Signals quality to the WordPress.org team and enables the "Live Preview" button on the listing.
- A visitor clicks once: a browser-isolated sandbox loads in 5 seconds with WooCommerce installed, sample orders seeded, and WPWing settings open with an active invoice preview.
- **Zero of the 7 tracked competitors offer this.**

### 2c. Review Generation Pipeline (Closing the Review Deficit)
1 review vs. WebToffee's 284 is the single largest organic search handicap. Mechanisms to close the gap:
- [ ] **Milestone Review Notice**: A dismissible, non-intrusive admin notice triggered after a store creates its **25th document**:
  *"You have generated 25 PDF invoices with WPWing! Has it saved you time? Please leave a 5-star review on WordPress.org."*
- [ ] **Setup Wizard Completion**: Ensure the final "All Done" screen of the Setup Wizard includes a friendly "Review on WordPress.org" link.
- [ ] **Support-to-Review**: Every resolved support thread concludes with a polite one-line review request. Resolved users have a >40% review conversion rate.

### 2d. Support Excellence
- [ ] Answer 100% of WordPress.org support forum threads within 24 hours.
- Maintain a 100% resolved threads ratio (Tyche resolves 8 of 20; WooCommerce.com sits at 2.4 stars due to neglected support).

### 2e. Localized Directory Search (The Asian & International Wedge)
- Merchants searching in their native language see localized plugins first.
- [ ] Submit translation strings to translate.wordpress.org starting with `readme.txt`:
  - **Bengali** (native team language + direct beneficiary of our bundled currency font)
  - **Hindi** (massive WooCommerce market, ₹ currency symbol fix)
  - **Spanish, French, German, Dutch, Italian, Brazilian Portuguese**

---

## 3. Organic Content Marketing (wpwing.com)

Directory search caps out as rankings solidify; Google long-tail search captures buyers searching for solutions to specific plugin frustrations.

### 3a. Commercial Intent Comparison Roundups
- *"Best Free PDF Invoice & Packing Slip Plugins for WooCommerce (2026 Comparison)"*
  - Honest breakdown comparing WP Overnight, WebToffee, Challan, and WPWing.
  - Highlights what each charges for vs. what WPWing includes free.
- *"Top WooCommerce PDF Invoice Plugins with Product Images"*
  - Target merchants who want thumbnails without paying €99/year for WP Overnight's Premium Templates add-on.
- *"Challan Alternative for WooCommerce"*
  - Target stores looking for a lightweight, actively maintained alternative to Challan.

### 3b. Long-Tail Problem-Solving How-Tos
- *"How to Show Taka (৳), Rupee (₹), or Baht (฿) on WooCommerce PDF Invoices without Broken Boxes"*
  - We are the only plugin that solves this out-of-the-box via bundled currency glyphs.
- *"How to Bulk Download WooCommerce Invoices as a Single Merged PDF"*
- *"How to Add a Scannable QR Code to WooCommerce Invoices & Shipping Labels"*
- *"How to Create Delivery Notes in WooCommerce for Free"*
- *"How to Search WooCommerce Orders by Invoice Number"*

### 3c. YouTube Video Tutorials
- 2-3 minute screen-recorded walkthroughs of each how-to.
- Video embeds in blog posts and readme documentation boost organic dwell time and rank in Google Video Carousels.

---

## 4. Community Distribution & Outreach

- **Facebook Communities**:
  - WordPress Bangladesh (massive local WooCommerce adoption).
  - WooCommerce Help & Share, Advanced WooCommerce.
  - Participate by answering questions on order fulfillment and invoicing; recommend the plugin organically when directly relevant.
- **Reddit (`r/woocommerce`, `r/wordpress`)**:
  - Provide technical advice on invoice generation, Dompdf memory limits, and HPOS compatibility.
- **Competitor Forum Gap Filling**:
  - Monitor unresolved invoice questions on the main WooCommerce forums.
- **Directory Roundup Outreach**:
  - Reach out to authors of existing "Best WooCommerce Invoice Plugins" listicles (WPBeginner, LearnWoo, WPMayor) pitching inclusion based on the 4-in-1 free lineup and currency support.

---

## 5. Pro Launch Preparation (Q4 2026)

When the active install base reaches 200+ and review count reaches 15+:
- Position Pro addon at the **$39-$49/year** sweet spot (under-pricing WebToffee's $69 and WP Overnight's €99).
- Pro feature set: Credit Notes, Proforma Invoices, Packing Picklists, Multi-language (WPML/Polylang), Cloud Backup (Dropbox/Google Drive).
- Launch campaign on Product Hunt, Post Status, and WordPress newsletters (wpMail.me, The Repository).

---

## Standing Weekly Marketing Routine

1. Ship regular feature/fix releases (recency = directory ranking freshness).
2. Triage support forum within 24 hours; convert happy users to reviews.
3. Publish 1 how-to tutorial on wpwing.com targeting long-tail keywords.
4. Review analytics: active install tier, daily downloads, keyword rankings, support metrics.
5. Monthly: update `COMPETITORS.md` and review milestone pacing against this plan.
