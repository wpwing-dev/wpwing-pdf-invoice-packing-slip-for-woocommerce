# Growth / Marketing Plan

Goal: grow from **10+ active installs** (5.0 rating, 1 review, snapshot
2026-08-22) to a self-sustaining install base that can support the Pro launch.

Written alongside [COMPETITORS.md](COMPETITORS.md) and [TODO.md](TODO.md).
Review monthly with the competitor snapshot.

## Targets

wordpress.org shows installs in brackets (10+, 20+, ... 100+, 200+, ...), and
growth compounds slowly at first. Honest targets:

| Milestone | Target date | Leading indicators |
|---|---|---|
| 50+ installs, 5 reviews | Oct 2026 | downloads/day > 10, cadence restored |
| 100+ installs, 10 reviews | Dec 2026 | first-page directory rank for 1 keyword |
| 500+ installs, 25 reviews | Mar 2027 | Pro launched, content ranking in Google |
| 1,000+ installs | Jun 2027 | organic installs exceed churn consistently |

Track weekly: active installs bracket, downloads/day (wordpress.org advanced
stats), review count, directory search position for "pdf invoice", "packing
slip", "invoice", support threads answered.

---

## 1. Win the wordpress.org directory (the only channel that matters at 10 installs)

Almost all installs at this scale come from directory search. The ranking
algorithm weights: readme keyword relevance, active install growth rate,
rating count and quality, support thread resolution, freshness / tested-up-to,
and translations. Every action below targets one of those inputs.

### 1a. Listing hygiene (this week, near-zero effort)

- [ ] Bump **Tested up to: 7.1** (leaders declare it; the directory warns users
      and demotes search placement for stale values).
- [ ] Resume the weekly release cadence - "Last updated: 1 month ago" is
      already visible on our page. Freshness is a ranking input we fully control.
- [ ] Review the 5 tags. Current: PDF, Invoice, Packing Slip, Packing List,
      WooCommerce. Consider swapping "Packing List" for "delivery note" now
      that the document type shipped (check tag search volume in the directory
      before swapping).
- [ ] Rewrite the readme **short description** around the highest-volume search
      phrase: "PDF invoice", "packing slip", "delivery note", "WooCommerce
      invoice" should all appear naturally in the first sentence.

### 1b. Live preview button (this month, high leverage, almost nobody does it)

- [ ] Add a WordPress Playground **blueprint** (`assets/blueprints/blueprint.json`
      in SVN) so the listing gets a "Live Preview" button: WooCommerce
      pre-installed, a sample order created, settings pre-filled with a demo
      logo. A visitor clicks once and sees a finished invoice.
- Very few plugins in this niche have it (none of our 7 tracked competitors do).
  It converts undecided visitors and signals quality to the directory team.

### 1c. Screenshots and media (this month)

- [ ] Rebuild the screenshot set: lead with the **finished PDF invoice**, not the
      settings screen. Order: invoice PDF, packing slip PDF, delivery note PDF,
      QR code close-up, orders-list bulk actions, settings with live preview.
- [ ] Annotated captions on every screenshot (captions are indexed).
- [ ] Professional banner (1544x500) and icon - the current listing must not
      look homemade next to WebToffee's.
- [ ] 60-90 second demo video (install -> wizard -> first invoice) embedded in
      the readme via YouTube; doubles as the YouTube channel seed (section 3).

### 1d. Review engine (continuous - the single biggest ranking lever)

1 review vs. WebToffee's 284 is the gap that matters most. Mechanisms:

- [ ] **In-plugin review prompt**: dismissible admin notice after ~25 generated
      documents ("Your store has generated 25 PDF invoices with us - would you
      leave a review?"). Once, dismissible forever, never blocks work -
      compliant with directory guidelines and effective because it triggers at
      a moment of delivered value.
- [ ] **Setup wizard finish screen** links to the review page (already planned
      in 1.14.0 - this is another reason to ship it now).
- [ ] **Support-to-review pipeline**: after every resolved support thread, one
      polite sentence asking for a review. Resolved-thread users convert best.
- [ ] Personally ask the existing 10+ installers (support forum, GitHub issue
      participants) - at this scale, individual outreach is feasible.

### 1e. Support excellence (continuous)

- [ ] Answer every forum thread within 24 hours. With near-zero volume today
      this is cheap, and the "resolved threads" ratio is displayed publicly and
      factored into ranking. Tyche resolves 8/20 - beating that is easy.

### 1f. Translations (this quarter)

Translated plugins surface in localized plugin directories (a far less
competitive search landscape) and translation contributions feed ranking.

- [ ] Seed translations via translate.wordpress.org for: **Bengali** (native
      team language + the currency-font differentiator targets this market),
      Spanish, German, French, Dutch, Italian, Brazilian Portuguese, Hindi.
- [ ] Readme first (that is what localized directories index), then the
      settings strings.

---

## 2. Positioning: pick fights we can win

We cannot out-feature WP Overnight or out-review WebToffee this year. Positioning
that works at our size:

- **"Lightweight and fast"** - already our readme angle; keep hammering it.
  The market leaders are visibly bloated (WP Overnight ships an e-invoicing
  stack most small stores never use). Target merchants who want invoices
  working in 5 minutes.
- **"Every currency symbol just works"** - the bundled currency font (Taka,
  Rupee, Lari, Riel...) is a genuine differentiator competitors leave as blank
  boxes. This is the wedge into South/Southeast Asian markets, which are also
  underserved by localized competition (only Challan targets them, and they are
  3 months stale).
- **"Actually supported"** - WooCommerce.com's official plugin is at 2.4 stars
  over dead support at $79/yr. Fast forum answers are marketing.

---

## 3. Content marketing (starts now, compounds by Q1 2027)

Directory search caps out; Google long-tail brings the next tier of installs.
All content lives on wpwing.com with the plugin as the answer.

- [ ] **Comparison content** (highest commercial intent):
  - "Best free PDF invoice plugins for WooCommerce (2026)" - honest roundup
    including competitors; we win by being current and thorough.
  - Head-to-heads: "WP Overnight vs WebToffee vs WPWing", "X alternative" pages
    for each competitor (searches for "challan plugin alternative" etc. are
    low-volume but near-100% intent).
- [ ] **Long-tail how-tos** (each maps to a feature we ship):
  - "How to add a QR code to WooCommerce invoices"
  - "How to show ৳/₹/฿ currency symbols on WooCommerce PDF invoices" (we are
    the only good answer on the internet for this one)
  - "How to bulk download WooCommerce invoices as one PDF"
  - "How to create WooCommerce delivery notes"
  - "How to search WooCommerce orders by invoice number"
- [ ] **YouTube**: screen-recorded versions of the top how-tos. Video results
      rank on Google for "how to" WooCommerce queries with thin competition.
- [ ] Cadence: 2 posts/month minimum; every feature release gets a matching
      how-to post and a changelog-driven social mention.

---

## 4. Community distribution (low cost, immediate)

- [ ] **Facebook groups**: WordPress Bangladesh (huge, team's home community),
      WooCommerce Community, WordPress Speed Up, various WooCommerce help
      groups. Answer invoice-related questions genuinely; mention the plugin
      only when it is the actual answer.
- [ ] **Reddit** r/woocommerce and r/wordpress: same rule - be the helpful
      answer, not the ad.
- [ ] **wordpress.org support forums of competitors' users**: never spam, but
      unanswered general "how do I make invoices" threads in the main
      WooCommerce forum are fair ground for helpful answers.
- [ ] **GitHub presence**: the repo is already public - add good-first-issue
      labels, a CONTRIBUTING note, and respond fast. Contributors become
      advocates and reviewers.
- [ ] **Outreach for roundups**: pitch inclusion to existing "best invoice
      plugin" listicles (WPBeginner-style sites refresh these yearly; being
      new, lightweight, and actively developed is the pitch). One accepted
      placement can outdo months of directory grinding.

---

## 5. Launch moments (Q4 2026)

- [ ] **Pro launch** (per UPDATE-PLAN month 4): launch on Product Hunt +
      relevant newsletters (wpMail.me, The Repository, Post Status). A
      launch-week lifetime or discount deal creates urgency.
- [ ] Consider **AppSumo/deal platforms** only after Pro is stable - good for
      cash and installs, hard on support and review averages; decide in Q1 2027.
- [ ] Every major free feature (setup wizard, shipping labels, drag-and-drop
      customizer) is a mini-launch: blog post + video + community posts +
      newsletter pitch.

---

## 6. What we deliberately skip (for now)

- **Paid ads**: "woocommerce pdf invoice" CPCs are bid up by incumbents with
  LTVs we cannot match at 10 installs. Revisit after Pro conversion data exists.
- **Affiliate program**: needs Pro revenue first; revisit Q1 2027.
- **E-invoicing content/features**: WP Overnight owns this narrative; entering
  it now dilutes the lightweight positioning (see TODO "Watching, not building").

---

## Standing weekly marketing checklist

- Ship the weekly release (cadence = freshness = ranking).
- Answer all support threads; ask resolved ones for a review.
- One community answer (Facebook/Reddit/forums) that genuinely helps someone.
- Log the weekly numbers: installs bracket, downloads/day, reviews, rank for
  "pdf invoice" in directory search.
- Monthly: refresh COMPETITORS.md, re-plan UPDATE-PLAN.md, review this plan's
  targets.
