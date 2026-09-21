=== PDF Invoice and Packing Slip for WooCommerce ===

Contributors:       wpwing, voboghure
Donate link:        https://wpwing.com/
Tags:               pdf invoice, packing slip, delivery note, shipping label, woocommerce
Requires at least:  4.8
Tested up to:       7.1
Requires PHP:       7.4
Stable tag:         1.16.0
License:            GPL-3.0-or-later
License URI:        https://www.gnu.org/licenses/gpl-3.0.html

Automate PDF invoices, packing slips, delivery notes & shipping labels for WooCommerce. Attach to emails, print in bulk. Fast & lightweight.


== Description ==

Streamline your eCommerce store's fulfillment, billing, and accounting with an all-in-one, ultra-fast WooCommerce PDF invoicing solution. Automatically generate professional **PDF invoices**, **packing slips**, **delivery notes**, and **shipping labels**, attach them directly to customer order emails, and print or download batches in seconds.

Whether you need legally compliant tax invoices for international buyers, price-free packing slips for your fulfillment warehouse, or shipping labels with scannable QR codes, this plugin automates your entire post-order workflow on autopilot.

Built strictly with performance, security, and modern WordPress standards, it runs lightning-fast on your server without database bloat, memory leaks, or checkout slowdowns.

✅ Tested OK with WooCommerce 11.0.1+
🚀 High-Performance Order Storage (HPOS) Compatible
⚡ Zero Bloat - No external telemetry, no background tracking scripts

---

### 🔥 4-IN-1 DOCUMENT SUITE (100% FREE) ###

Unlike other plugins that charge €69 to €99 per year for basic document types, WPWing gives you all 4 essential store documents completely free:

* 📑 **PDF Invoices:** Fully compliant tax invoices with customizable sequential numbering, tax breakdowns, store branding, and automated email attachments.
* 📦 **PDF Packing Slips:** Warehouse-ready packing lists showing items and quantities without prices, perfect for picking and packing teams.
* 🚚 **Delivery Notes:** Price-free delivery slips that travel inside the parcel with item summaries, shipping addresses, and optional customer order notes.
* 🏷️ **Shipping Labels:** Clean shipping cards with prominent recipient address, sender details, package weight, shipping method, and a scannable QR code.

---

### ⚡ KEY FEATURES AT A GLANCE ###

* 🖼️ **Product Thumbnails in Item Tables (New in 1.16.0):** Display product images (32px, 48px, or 64px), SKU, and product weight in item tables across all document types. Engineered with memory-safe image downscaling and local disk caching to prevent Dompdf crashes.
* 🧙 **2-Minute Setup Wizard:** First-time store owners can configure shop name, address, logo, paper size, and email attachments in under two minutes with our skippable onboarding wizard.
* 👁️ **Live Interactive Preview Panel:** Preview your changes live in real time directly from the settings panel before generating documents for real customers.
* 📦 **1-Click Bulk Downloads (Merged PDF or ZIP):** Save hours during shipping rushes. Select orders and download all invoices or packing slips as a single combined PDF or a compressed ZIP archive.
* 🌍 **Native International Currency Support:** Every WooCommerce currency symbol renders cleanly on your PDFs without broken "tofu" boxes - including the Bangladeshi Taka (৳), Indian Rupee (₹), Thai Baht (฿), and dozens more.
* 📱 **Dynamic Scannable QR Codes:** Add a scannable QR code to invoices and shipping labels - linking directly to the customer's order verification page or custom text tokens.
* 🔢 **Sequential & Yearly Reset Numbering:** Full accounting compliance. Configure custom invoice numbering with personalized prefixes, suffixes, dynamic zero-padding, and optional automatic yearly resets.
* 🛡️ **Free Order Suppression:** Automatically skip invoice generation or email attachment on free ($0.00) orders when enabled.
* 🔍 **Search Orders by Invoice Number:** Find any order in seconds by typing its invoice number directly into the WooCommerce order search box (works on classic and HPOS).
* 👤 **Customer My-Account Downloads:** Customers can view and download past invoices from their My Account order history, or via the `[wpwing_invoice]` shortcode anywhere on your site.
* 🔒 **Rock-Solid Security:** 100% CSRF-protected with WordPress nonces. Strict capability checks on every document action.

---

### 📊 HOW WE COMPARE ###

| Feature | WP Overnight | WebToffee | WPWing (Free) |
|---|---|---|---|
| **Free Document Types** | 2 (Invoice, Packing) | 4 (Invoice, Packing, Delivery, Shipping) | **All 4 Free** |
| **Product Thumbnails in Tables** | Paid Add-on (EUR 99/yr) | Paid Pro ($69/yr) | **100% Free** |
| **Bulk Merged PDF & ZIP** | Paid Add-on (EUR 99/yr) | Paid Pro ($69/yr) | **100% Free** |
| **QR Code on Documents** | Paid Add-on | Paid Pro | **100% Free** |
| **Native Asian Currencies (৳, ₹, ฿)** | Broken tofu boxes | Requires free add-on | **Bundled Native Font** |
| **Setup Wizard & Live Preview** | No | Multi-step form | **Wizard + Live Preview** |
| **Price** | Free / EUR 99+ | Free / $69+ | **100% Free** |

---

### 💡 WHY SHOP OWNERS LOVE WPWING ###
Most WooCommerce PDF invoice plugins on the market fall into two extremes: either they are abandoned with broken support, or they are bloated with heavy e-invoicing dependencies that slow down your server.

WPWing hits the sweet spot: **clean, modern code, blazing fast document generation, and zero paywalls on essential shipping features.**

### 🧑‍💻 Fast & Friendly Support
Need help or have a feature suggestion? We actively maintain our plugins and respond quickly on the WordPress.org support forums.


== Installation ==

### 🛠️ Automatic Installation (Recommended):
1. In your WordPress dashboard, navigate to **Plugins > Add New**.
2. Search for *"PDF Invoice and Packing Slip for WooCommerce"*.
3. Click **Install Now**, then click **Activate**.
4. The automatic Setup Wizard will launch to help you set up your logo and details in 2 minutes.

### 📁 Manual Installation:
1. Download the plugin zip file and unpack it.
2. Upload the `wpwing-pdf-invoice-packing-slip-for-woocommerce` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Open **WooCommerce > PDF Invoices** to configure your store settings.


== Frequently Asked Questions ==

= Can I show product images and thumbnails on invoices? =
Yes! In **WooCommerce > PDF Invoices > Template**, simply toggle the "Show product thumbnail" checkbox and choose your desired size (32x32, 48x48, or 64x64). Thumbnails are automatically cached and downscaled to keep PDF generation fast and memory-safe.

= Can I bulk download invoices as a single merged PDF? =
Yes! From your WooCommerce Orders list, select the orders you want to print, choose **Download Invoices (Merged PDF)** or **Download Invoices (ZIP)** from the Bulk Actions dropdown, and click **Apply**.

= Does this support non-Latin currency symbols like Taka (৳) and Rupee (₹)? =
Yes! Most PDF plugins render non-Latin currency symbols as empty boxes or question marks because standard PDF fonts lack those glyphs. WPWing includes a custom bundled currency font that renders symbols for the Bangladeshi Taka (৳), Indian Rupee (₹), Thai Baht (฿), Philippine Peso (₱), and many more perfectly out-of-the-box.

= Can I prevent invoices from being sent on free ($0) orders? =
Yes! Under **WooCommerce > PDF Invoices > General**, enable the "Disable for free orders" option. Free orders will not generate or attach invoice PDFs to customer emails.

= Can I show an invoice download link anywhere on my site? =
Yes! Use the `[wpwing_invoice]` shortcode. On the Thank You page or My Account order details, it detects the order automatically; elsewhere, use `[wpwing_invoice order_id="123"]`. You can customize the link text with the `label` attribute.

= Is this plugin compatible with WooCommerce HPOS (High-Performance Order Storage)? =
Yes! WPWing fully supports High-Performance Order Storage (HPOS) and standard custom post types.

= Do I need coding knowledge to customize my invoices? =
Not at all. You can upload your store logo, add company details, adjust invoice numbering, toggle QR codes, and customize colors directly from the settings panel. If you are a developer, we also provide a Custom CSS box and rich action/filter hooks.

= Can I contribute or report bugs? =
Yes! This plugin is actively maintained on [GitHub](https://github.com/wpwing-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce). We welcome pull requests and issue reports.


== Screenshots ==

1. PDF Invoice template preview
2. PDF Packing Slip template preview
3. WooCommerce order details page with PDF invoice metabox actions
4. General settings screen - numbering format, automation, and email attachments
5. Template settings screen with live interactive preview panel
6. Delivery Note template preview
7. Shipping Label with QR code template preview
8. WooCommerce orders list with bulk PDF actions dropdown

== Changelog ==

= 1.16.0 - 2026-09-21 =

* New: Product thumbnails in item tables - toggle thumbnail display and choose size (32x32, 48x48, 64x64) across all document types (invoices, packing slips, delivery notes, shipping labels) with memory-safe downscaling and local caching.
* New: Product SKU and Weight column visibility options across all document types in both Default and Modern templates.
* Fix: Decoupled database migration routine from plugin semver releases, preventing redundant table migration queries on every release.
* Fix: Suppress PDF invoice email attachments on free orders ($0.00) when the "Disable for free orders" setting is enabled.
* Fix: Relocated invoice QR code adjacent to totals summary table to optimize document layout and eliminate dead space.
* Fix: Resolved template footer overlap on shorter pages and preview panels by replacing absolute positioning with normal document flow.
* Hardening: Wrapped invoice counter advisory lock acquisition and release in try-finally construct to guarantee lock release.

= 1.15.0 - 2026-09-07 =

* New: Shipping Label document type - a price-free label with a prominent recipient shipping address, sender details, order weight, shipping method, and an optional scannable QR code linking back to the order. Create it manually, in bulk (ZIP or merged PDF), or automatically on the order statuses you choose - just like invoices, packing slips, and delivery notes.
* New: Shipping label template setting (Default or Modern), included in the same Custom CSS box as the other document types.

= 1.14.0 - 2026-08-31 =

* New: Setup Wizard - a first-run wizard walks you through shop name and address, logo upload, paper size, auto-generate statuses, and invoice email attachments. Launches automatically the first time you activate the plugin, is skippable at every step, and can be re-launched any time from a link on the Settings page.
* Fix: The company logo could fail to appear on generated PDFs (showing "Image not found" instead) on some hosts, including most local dev environments. The logo is now embedded directly rather than fetched back over the network.
* Fix: Order metabox action buttons could overflow the sidebar on narrow screens. The destructive action is now labeled "Delete" instead of "Cancel" for clarity.
* Fix: The invoice preview panel in Settings could clip the right side of the invoice on narrower screens instead of showing a scrollbar.

= 1.13.0 - 2026-08-24 =

* New: System Status tab in Settings - shows plugin, WordPress, PHP, WooCommerce and Dompdf versions, the document save folder and font cache folder with a writable check, and the active document types. Includes a one-click "Copy system info" button for support requests.
* New: Settings search - a search box above the settings tabs filters the visible tab's rows in real time as you type, and stays applied when you switch tabs.
* Fix: A brand-new install could trigger a PHP deprecation notice ("Passing null to parameter of type string") the first time the Settings page was opened, before Save Changes had ever been clicked.
* Compatibility: Tested up to WordPress 7.1.

= 1.12.0 - 2026-07-19 =

* New: Delivery Note document type - a price-free document listing the items and shipping address, made to travel with the parcel. Create, view, or cancel it from the order page metabox, just like invoices and packing slips.
* New: Auto-generate delivery notes on the order statuses you choose, with the same once-per-order behaviour as invoices and packing slips.
* New: Delivery note bulk actions on the orders list - generate for selected orders, or download them as a ZIP or one merged PDF.
* New: Delivery note template setting (Default or Modern) and an option to print the customer's checkout note on the delivery note.
* Improvement: The Custom CSS box now also applies to delivery notes.

= 1.11.0 - 2026-07-12 =

* New: [wpwing_invoice] shortcode - drop it on any page to show a secure invoice download link. It automatically detects the order on the Thank You page and in My Account, or accepts an explicit order_id attribute. Use the label attribute to customize the link text.
* New: Search orders by invoice number. Type an invoice number into the search box on the WooCommerce orders list and the matching order appears - works on both the classic and High-Performance Order Storage screens.

= 1.10.0 - 2026-07-05 =

* New: Bulk download invoices and packing slips. Select any number of orders on the orders list and download them as a single ZIP file, or as one merged PDF - any documents that don't exist yet are generated automatically.
* New: International currency support. Currency symbols that previously printed as an empty box - such as the Bangladeshi Taka (৳), Georgian Lari (₾), Cambodian Riel (៛), and many more - now render correctly on every invoice, thanks to a bundled currency font.
* New: Custom CSS box in Template Settings. Fine-tune the look of your invoices and packing slips without editing any template files.
* Improvement: If PDF generation ever fails, you now get a clear admin notice instead of a blank page or a broken download.
* Fix: When the font cache folder is missing or not writable, invoices still generate using the built-in font instead of failing outright.
* Fix: A currency symbol added by an incomplete font cache could push invoice rows out of alignment. The bundled font now matches the default line spacing and repairs a stale cache automatically.

= 1.9.0 - 2026-06-28 =

* New: Added an optional QR code on invoices - configure it from Template Settings. Encode the customer's order view URL, or your own custom text with {order_number} and {invoice_number} placeholders.
* Fix: Bulk generating invoices or packing slips no longer duplicates the document content. A template hook left registered between documents caused the second PDF in a batch to render its content twice, the third three times, and so on.
* Fix: The template hook cleanup now always runs even when PDF generation fails partway, so a failed document can no longer corrupt the next one generated in the same request.
* Fix: Declared the packing slip date property to avoid a dynamic property deprecation notice on PHP 8.2+.
* Note: Minimum required PHP version is now 7.4.

= 1.8.1 - 2026-06-21 =

* Fix: Yearly invoice counter reset could race under concurrent Jan 1 requests, causing two invoices to receive the same number. The reset now runs inside the advisory lock alongside the counter increment.
* Fix: When no prefix or suffix was configured, the literal text "prefix" or "suffix" appeared verbatim on invoices. These now fall back to an empty string.
* Fix: When PDF generation failed, the upload directory itself could be added as an email attachment. The attachment is now skipped unless the document was saved successfully and has a valid file path.
* Fix: Admin success and warning notices could be triggered by a crafted URL with no authentication check. Notices are now verified with a nonce before being displayed.
* Fix: An unexpected error during auto-generation left an internal reentrancy guard permanently set, silently suppressing all further auto-generation for the rest of that request. The guard is now always reset even if an error occurs.
* Fix: Viewing or emailing a PDF was vulnerable to directory traversal via a tampered file path stored in order meta; bulk-action result notices now require a nonce before rendering; PDF generation error messages are sanitised before being stored in admin notices.
* Fix: Invoice table no longer crashes on orders with zero-quantity line items; shipping and fee rows now include the SKU column cell when SKU display is enabled; shipping tax is summed as a float rather than an integer; the Subtotal row shows product lines only and no longer double-counts fees and shipping; zero-cost shipping lines are suppressed; empty product SKUs fall back to "-".
* Fix: The HTML preview endpoint always resets its document global after rendering, even if an error occurs mid-template; bulk generation count now reflects only orders where the PDF was actually saved; PDF open/download behaviour is now resolved per document type; payment due date field description clarifies it is calculated from order creation date; logo URL field is no longer read-only.

= 1.8.0 - 2026-06-14 =

* New: Added a payment due date to invoices - configure the offset in days from General Settings.
* New: Added an HTML preview button in the order metabox - check the invoice or packing slip layout without generating a PDF.
* Fix: Downloaded PDFs no longer include stray HTML content after the file data, which could corrupt the download on some browsers.
* Fix: Creating an invoice or packing slip from a draft or incomplete order no longer causes a fatal error.
* Fix: When generating multiple PDFs in bulk or via email attachments, templates no longer bleed into each other from leftover hooks of a previous generation.
* Fix: If PDF generation fails partway through, the invoice number is now held in reserve and reused on the next attempt - no more gaps in your invoice sequence from a failed save.
* Fix: Accessing invoice details on an order that could not be loaded no longer causes a fatal error.

= 1.7.0 - 2026-06-07 =

* New: Added an option to show the customer's note (entered at checkout) on the invoice.
* New: Added an option to control tax display in invoice totals - show each tax class as a separate line, or collapse them into a single tax total.
* New: Added a Documentation page accessible directly from the Plugins list.
* Improvement: Auto-generate status and email attachment settings now use checkboxes instead of a multi-select list - much easier to pick and toggle options.
* Fix: When a PDF fails to generate, an admin notice now appears so you always know something went wrong.

= 1.6.0 - 2026-05-30 =

* New: Live invoice preview panel - see exactly how your invoice looks while you're adjusting settings, without saving first.
* New: Smarter invoice numbering - use simple tokens like {number}, {year}, and {month} to build your own format (e.g. INV-{number}/{year}{month}). Existing formats are upgraded automatically.
* Fix: PDFs no longer crash on orders that were created without a date (e.g. via import or migration tools).
* Fix: Packing slip incorrectly showed "Invoice Date" — now correctly shows "Packing Date".
* Fix: Company logo is now properly sized and right-aligned on both invoice and packing slip templates.
* Fix: Long addresses and notes no longer cause unwanted scrollbars inside the invoice preview.
* Improvement: The invoice preview panel stays hidden until you need it, keeping the settings page clean.
* Compatibility: Tested and confirmed working with WooCommerce 10.8.1.

= 1.5.1 - 2026-05-22 =

* Compatibility: Tested and confirmed working with WordPress 7.0.
* Improved plugin description for clarity.
* Dev: Renamed all internal hooks, filters, and constants from wpwing_wcpi_ to wpwing_wcpdf_ for better long-term naming. Existing data is migrated automatically — no action needed.

= 1.5.0 - 2026-05-18 =

* New: Auto-generate invoice and packing slip on configurable order status change.
* New: Attach invoice and packing slip PDF to any WooCommerce transactional email.
* New: Bulk generate invoices and packing slips from the WooCommerce orders list.
* New: Invoice and packing slip status column on the orders list.
* New: Structured company information fields — address, city, ZIP, country, phone, email, and VAT/tax ID.
* New: Paper size selection (A4 or Letter).
* New: Option to show shipping address on invoice.
* New: Option to show product SKU column on invoice.
* New: Yearly invoice number reset option.
* New: Option to skip invoice generation for free (zero-total) orders.
* New: Multiple template selection — Default and Modern templates included.
* New: Live invoice and packing slip preview in Template settings.
* New: High-Performance Order Storage (HPOS) compatibility.
* Security: All document actions (create, view, cancel) are now CSRF-protected with nonces.
* Update: My Account invoice button uses WooCommerce native action — no longer breaks flex layout.
* Update: Cancel invoice/packing slip now shows a confirmation dialog before proceeding.
* Update: Admin notices confirm success or failure after every document action.
* Compatibility check with WordPress v6.9 and WooCommerce v10.7.0.
* Few minor improvements.

= 1.4.3 - 2024-04-22 =

* Compatibility check with WordPress v6.5 and WooCommerce v8.8.
* Few minor improvements.

= 1.4.2 - 2024-02-17 =

* Compatibility check with WC's latest version.
* Few minor improvements.

= 1.4.1 - 2024-01-20 =

* Update: DOM PDF version 2.0.4
* Compatibility check with WP's latest version.
* Compatibility check with WC's latest version.
* Few minor improvements.

= 1.4.0 - 2023-06-17 =

* Update: Remove WPWing prefix from the plugin name
* Update: Invoice and Packing slip open in new tab
* Few minor improvements.

= 1.3.4 - 2023-04-17 =

* Fix: Refactor and update the DOMPDF library.

= 1.3.3 - 2022-07-12 =

* Fix: Refactor and update code.

= 1.3.2 - 2022-06-17 =

* Fix: Deactive if the dependent WooCommerce plugin is not activated.
* Few minor improvements.

= 1.3.1 - 2022-06-08 =

* Update: Add download invoice feature in customer "My Account > Orders" section.
* Few minor improvements.

= 1.3.0 - 2022-05-08 =

* Update: Invoice settings template UI/UX.
* Update: DOMPDF version updated to 1.2.2
* Few minor improvements.

= 1.2.0 - 2022-02-04 =

* Update: Add global condition for sending email to the customer billing address.
* Update: DOMPDF version updated to 1.1.1
* Fix: Appropriate text for Invoice Settings checkbox.
* Few minor improvements.

= 1.1.0 - 2021-12-25 =

* Update: Send PDF Invoice attached with email when admin creates an invoice.
* Few minor bug fixes and improvements.

= 1.0.1 - 2021-10-15 =

* Update: Settings text.
* Update: Set the default invoice number if empty in the settings section.

= 1.0.0 - 2021-10-14 =

* initial release.

