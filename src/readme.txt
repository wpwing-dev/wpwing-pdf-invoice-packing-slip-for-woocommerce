=== PDF Invoice and Packing Slip for WooCommerce ===

Contributors:       wpwing, voboghure
Donate link:        https://wpwing.com/
Tags:               PDF, Invoice, Packing Slip, Packing List, WooCommerce
Requires at least:  4.8
Tested up to:       7.0
Requires PHP:       7.4
Stable tag: 1.11.0
License:            GPL-3.0-or-later
License URI:        https://www.gnu.org/licenses/gpl-3.0.html

Automatically generate, print, and attach professional PDF invoices and packing slips to WooCommerce emails. Clean, lightweight, and fast.


== Description ==

Streamline your eCommerce store's fulfillment, billing, and accounting workflows with a clean, lightweight WooCommerce PDF invoicing solution. This plugin automatically generates professional PDF invoices and packing slips, attaches them directly to your standard WooCommerce customer emails, and allows you to securely download or print them directly from your WordPress admin dashboard.

Whether you need to send a legal tax invoice to a buyer, include a printed packing list inside a shipping box, or keep an organized archive of your store's financial records, this plugin automates the entire process on autopilot.

Built strictly with performance, security, and modern WordPress standards in mind, it handles document generation seamlessly on your server. It ensures your site remains lightning-fast without bloating your database, overloading your server memory, or slowing down your customer checkout conversion rates.

✅ Tested OK with WooCommerce 10.8.1+
🚀 High-Performance Order Storage (HPOS) Compatible

### 🔥 KEY FEATURES ###

* 📑 **Automated Email PDF Attachment:** Automatically generate and attach PDF invoices to specific WooCommerce order status emails, including Processing Order, Completed Order, or Customer Invoice / Order Details.
* 📦 **Efficient Order Management & Bulk Actions:** Save time during busy shipping hours. Quickly generate, download, or print multiple invoices and packing slips directly from your WooCommerce orders list page - grab a whole batch as a single ZIP file or one merged PDF in a couple of clicks. Find any order instantly by typing its invoice number into the order search box.
* 🌍 **Full International Currency Support:** Every WooCommerce currency symbol renders perfectly on your PDFs - including the Bangladeshi Taka (৳), Indian Rupee (₹), Thai Baht (฿), and dozens more that other plugins leave as blank boxes.
* 🎨 **Multiple Beautiful Templates:** Switch between beautifully formatted layouts including our crisp "Default" and modern minimalist designs that print perfectly on standard A4 or Letter sizes. Add your own Custom CSS to fine-tune any detail.
* 📱 **QR Code on Invoice:** Add a scannable QR code to your invoices - link straight to the customer's order page, or encode your own custom text with order and invoice number placeholders.
* ⚙️ **Fully Customizable Store Branding:** Create a professional look that matches your business identity. Easily upload your store logo, input company address details, insert tax registration information (VAT/GST/Tax ID), and add custom terms or footer notes.
* 🔢 **Sequential Invoice Numbering:** Keep your business accounting perfectly compliant. Set up custom invoice numbers with personalized prefixes, suffixes, dynamic padding length, and an option for an automatic yearly number reset.
* 👤 **Secure Customer My-Account Downloads:** Logged-in customers can easily navigate to their account page to view, print, or download past PDF invoices for their personal bookkeeping. Prefer a download link somewhere else? Drop the [wpwing_invoice] shortcode on any page.
* 🔒 **Developer-Friendly & Robust Security:** Built with optimized, clean code ensuring theme compatibility. All critical document actions (create, view, cancel) are fully CSRF-protected with secure WordPress nonces.

### 💡 WHY CHOOSE THIS PLUGIN? ###
Many alternative WooCommerce invoice layout tools suffer from extreme feature bloat, heavy database queries, or outdated user interfaces that confuse shop managers.

This plugin cuts through the noise by focusing purely on doing two essential tasks perfectly: delivering flawless, elegant PDF invoices and generating accurate packing slips - fast. We don't bundle unnecessary scripts or tracking codes. You get a reliable, high-speed automation tool engineered to help you run your business efficiently.

### 🛣️ FUTURE ROADMAP ###
We are actively building features to make your shop management even smoother. Look out for these upcoming documentation formats in future releases:
* Proforma Invoice generation
* Delivery Notes customization
* Print-ready Shipping Labels

### 🧑‍💻 Dedicated Support
At WPWing, we are committed to building high-quality, lightweight utility plugins for WordPress and WooCommerce. Our dedicated support team responds rapidly to the support forums to help you resolve any issues instantly.


== Installation ==

### 🛠️ Modern Way (Recommended): ###
1. Navigate to your WordPress Dashboard and click on **Plugins > Add New**.
2. Search for *"PDF Invoice and Packing Slip for WooCommerce"*.
3. Click **Install Now**, then click **Activate**.

### 📁 Traditional Way: ###
1. Download the plugin zip file and extract it.
2. Upload the folder `wpwing-pdf-invoice-packing-slip-for-woocommerce` to your `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Follow our official [Documentation](https://wpwing.com/docs/) to configure your layout settings.


== Frequently Asked Questions ==

= Do I need coding skills to use this plugin? =
Not at all! This plugin is designed to be plug-and-play. It features a highly intuitive settings dashboard that lets you upload your logo, add business details, and configure your invoices in just a few clicks.

= Do I need to edit my current WordPress theme files? =
No, it works seamlessly out-of-the-box with any standard WordPress theme. You just need to activate the plugin, and the system handles the PDF generation automatically.

= Can I show an invoice download link anywhere on my site? =
Yes! Use the `[wpwing_invoice]` shortcode. On the order Thank You page or a My Account order page it finds the order automatically; anywhere else, pass the order explicitly with `[wpwing_invoice order_id="123"]`. You can change the link text with the `label` attribute. The link only appears for the order's own customer (logged in) or store managers, and only after the invoice has been generated.

= Is the plugin compatible with WooCommerce HPOS? =
Yes! The plugin fully supports High-Performance Order Storage (HPOS) to ensure maximum database efficiency and site speed for modern WooCommerce environments.

= Can I contribute to the code or report a bug? =
You’re more than welcome! This plugin is actively developed and hosted on [GitHub](https://github.com/wpwing-dev/wpwing-pdf-invoice-packing-slip-for-woocommerce), where you can openly create issues or submit pull requests.


== Screenshots ==

1. PDF Invoice (sample).
2. PDF Packing List (sample).
3. Create PDF from the order admin page.
4. General settings page.
5. Template settings page.


== Changelog ==

= 1.11.0 - 12/07/2026 =

* New: [wpwing_invoice] shortcode - drop it on any page to show a secure invoice download link. It automatically detects the order on the Thank You page and in My Account, or accepts an explicit order_id attribute. Use the label attribute to customize the link text.
* New: Search orders by invoice number. Type an invoice number into the search box on the WooCommerce orders list and the matching order appears - works on both the classic and High-Performance Order Storage screens.

= 1.10.0 - 05/07/2026 =

* New: Bulk download invoices and packing slips. Select any number of orders on the orders list and download them as a single ZIP file, or as one merged PDF - any documents that don't exist yet are generated automatically.
* New: International currency support. Currency symbols that previously printed as an empty box - such as the Bangladeshi Taka (৳), Georgian Lari (₾), Cambodian Riel (៛), and many more - now render correctly on every invoice, thanks to a bundled currency font.
* New: Custom CSS box in Template Settings. Fine-tune the look of your invoices and packing slips without editing any template files.
* Improvement: If PDF generation ever fails, you now get a clear admin notice instead of a blank page or a broken download.
* Fix: When the font cache folder is missing or not writable, invoices still generate using the built-in font instead of failing outright.
* Fix: A currency symbol added by an incomplete font cache could push invoice rows out of alignment. The bundled font now matches the default line spacing and repairs a stale cache automatically.

= 1.9.0 - 28/06/2026 =

* New: Added an optional QR code on invoices - configure it from Template Settings. Encode the customer's order view URL, or your own custom text with {order_number} and {invoice_number} placeholders.
* Fix: Bulk generating invoices or packing slips no longer duplicates the document content. A template hook left registered between documents caused the second PDF in a batch to render its content twice, the third three times, and so on.
* Fix: The template hook cleanup now always runs even when PDF generation fails partway, so a failed document can no longer corrupt the next one generated in the same request.
* Fix: Declared the packing slip date property to avoid a dynamic property deprecation notice on PHP 8.2+.
* Note: Minimum required PHP version is now 7.4.

= 1.8.1 - 21/06/2026 =

* Fix: Yearly invoice counter reset could race under concurrent Jan 1 requests, causing two invoices to receive the same number. The reset now runs inside the advisory lock alongside the counter increment.
* Fix: When no prefix or suffix was configured, the literal text "prefix" or "suffix" appeared verbatim on invoices. These now fall back to an empty string.
* Fix: When PDF generation failed, the upload directory itself could be added as an email attachment. The attachment is now skipped unless the document was saved successfully and has a valid file path.
* Fix: Admin success and warning notices could be triggered by a crafted URL with no authentication check. Notices are now verified with a nonce before being displayed.
* Fix: An unexpected error during auto-generation left an internal reentrancy guard permanently set, silently suppressing all further auto-generation for the rest of that request. The guard is now always reset even if an error occurs.
* Fix: Viewing or emailing a PDF was vulnerable to directory traversal via a tampered file path stored in order meta; bulk-action result notices now require a nonce before rendering; PDF generation error messages are sanitised before being stored in admin notices.
* Fix: Invoice table no longer crashes on orders with zero-quantity line items; shipping and fee rows now include the SKU column cell when SKU display is enabled; shipping tax is summed as a float rather than an integer; the Subtotal row shows product lines only and no longer double-counts fees and shipping; zero-cost shipping lines are suppressed; empty product SKUs fall back to "-".
* Fix: The HTML preview endpoint always resets its document global after rendering, even if an error occurs mid-template; bulk generation count now reflects only orders where the PDF was actually saved; PDF open/download behaviour is now resolved per document type; payment due date field description clarifies it is calculated from order creation date; logo URL field is no longer read-only.

= 1.8.0 - 14/06/2026 =

* New: Added a payment due date to invoices - configure the offset in days from General Settings.
* New: Added an HTML preview button in the order metabox - check the invoice or packing slip layout without generating a PDF.
* Fix: Downloaded PDFs no longer include stray HTML content after the file data, which could corrupt the download on some browsers.
* Fix: Creating an invoice or packing slip from a draft or incomplete order no longer causes a fatal error.
* Fix: When generating multiple PDFs in bulk or via email attachments, templates no longer bleed into each other from leftover hooks of a previous generation.
* Fix: If PDF generation fails partway through, the invoice number is now held in reserve and reused on the next attempt - no more gaps in your invoice sequence from a failed save.
* Fix: Accessing invoice details on an order that could not be loaded no longer causes a fatal error.

= 1.7.0 - 07/06/2026 =

* New: Added an option to show the customer's note (entered at checkout) on the invoice.
* New: Added an option to control tax display in invoice totals - show each tax class as a separate line, or collapse them into a single tax total.
* New: Added a Documentation page accessible directly from the Plugins list.
* Improvement: Auto-generate status and email attachment settings now use checkboxes instead of a multi-select list - much easier to pick and toggle options.
* Fix: When a PDF fails to generate, an admin notice now appears so you always know something went wrong.

= 1.6.0 - 30/05/2026 =

* New: Live invoice preview panel - see exactly how your invoice looks while you're adjusting settings, without saving first.
* New: Smarter invoice numbering - use simple tokens like {number}, {year}, and {month} to build your own format (e.g. INV-{number}/{year}{month}). Existing formats are upgraded automatically.
* Fix: PDFs no longer crash on orders that were created without a date (e.g. via import or migration tools).
* Fix: Packing slip incorrectly showed "Invoice Date" — now correctly shows "Packing Date".
* Fix: Company logo is now properly sized and right-aligned on both invoice and packing slip templates.
* Fix: Long addresses and notes no longer cause unwanted scrollbars inside the invoice preview.
* Improvement: The invoice preview panel stays hidden until you need it, keeping the settings page clean.
* Compatibility: Tested and confirmed working with WooCommerce 10.8.1.

= 1.5.1 - 22/05/2026 =

* Compatibility: Tested and confirmed working with WordPress 7.0.
* Improved plugin description for clarity.
* Dev: Renamed all internal hooks, filters, and constants from wpwing_wcpi_ to wpwing_wcpdf_ for better long-term naming. Existing data is migrated automatically — no action needed.

= 1.5.0 - 18/05/2026 =

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

= 1.4.3 - 22/04/2024 =

* Compatibility check with WordPress v6.5 and WooCommerce v8.8.
* Few minor improvements.

= 1.4.2 - 17/02/2024 =

* Compatibility check with WC's latest version.
* Few minor improvements.

= 1.4.1 - 20/01/2024 =

* Update: DOM PDF version 2.0.4
* Compatibility check with WP's latest version.
* Compatibility check with WC's latest version.
* Few minor improvements.

= 1.4.0 - 17/06/2023 =

* Update: Remove WPWing prefix from the plugin name
* Update: Invoice and Packing slip open in new tab
* Few minor improvements.

= 1.3.4 - 17/04/2023 =

* Fix: Refactor and update the DOMPDF library.

= 1.3.3 - 12/07/2022 =

* Fix: Refactor and update code.

= 1.3.2 - 17/06/2022 =

* Fix: Deactive if the dependent WooCommerce plugin is not activated.
* Few minor improvements.

= 1.3.1 - 08/06/2022 =

* Update: Add download invoice feature in customer "My Account > Orders" section.
* Few minor improvements.

= 1.3.0 - 08/05/2022 =

* Update: Invoice settings template UI/UX.
* Update: DOMPDF version updated to 1.2.2
* Few minor improvements.

= 1.2.0 - 04/02/2022 =

* Update: Add global condition for sending email to the customer billing address.
* Update: DOMPDF version updated to 1.1.1
* Fix: Appropriate text for Invoice Settings checkbox.
* Few minor improvements.

= 1.1.0 - 25/12/2021 =

* Update: Send PDF Invoice attached with email when admin creates an invoice.
* Few minor bug fixes and improvements.

= 1.0.1 - 15/10/2021 =

* Update: Settings text.
* Update: Set the default invoice number if empty in the settings section.

= 1.0.0 - 14/10/2021 =

* initial release.

