import { test, expect, type Page } from '../fixtures';

type DocType = {
    key: 'invoice' | 'packing' | 'delivery' | 'shipping_label';
    rowLabel: string;
    createdNotice: string;
    deletedNotice: string;
};

const DOC_TYPES: DocType[] = [
    { key: 'invoice', rowLabel: 'Invoice:', createdNotice: 'Invoice created successfully.', deletedNotice: 'Invoice has been deleted.' },
    { key: 'packing', rowLabel: 'Packing Slip:', createdNotice: 'Packing slip created successfully.', deletedNotice: 'Packing slip has been deleted.' },
    { key: 'delivery', rowLabel: 'Delivery Note:', createdNotice: 'Delivery note created successfully.', deletedNotice: 'Delivery note has been deleted.' },
    { key: 'shipping_label', rowLabel: 'Shipping Label:', createdNotice: 'Shipping label created successfully.', deletedNotice: 'Shipping label has been deleted.' },
];

function docRow(page: Page, rowLabel: string) {
    return page.locator('.wpwing-wcpdf-doc-row').filter({
        has: page.locator('.wpwing-wcpdf-doc-label', { hasText: rowLabel }),
    });
}

// The seeded "pending" order never gets a document pre-generated, so it's a
// stable, uniquely identifiable starting point for exercising the full
// create -> view/preview -> delete lifecycle without disturbing orders that
// other specs rely on being pre-populated.
async function gotoPendingOrderEditPage(page: Page) {
    await page.goto('/wp-admin/edit.php?post_type=shop_order&post_status=wc-pending');
    const row = page.locator('.wp-list-table tbody tr').first();
    await expect(row).toBeVisible();

    const editHref = await row.getByRole('link', { name: /^#\d+/ }).getAttribute('href');
    if (!editHref) {
        throw new Error('Could not find an edit link for the seeded pending order.');
    }
    await page.goto(editHref);
}

for (const doc of DOC_TYPES) {
    test.describe(doc.rowLabel.replace(':', ''), () => {
        test('create, view, preview, and delete', async ({ page }) => {
            await gotoPendingOrderEditPage(page);

            await docRow(page, doc.rowLabel).locator('.wpwing_wcpdf_create_invoice').click();
            // WooCommerce wraps core admin_notices in a container it hides on its own
            // admin screens (a WC core quirk, not this plugin's concern) - assert the
            // notice made it into the DOM rather than that it's visually visible.
            await expect(page.getByText(doc.createdNotice)).toBeAttached();

            const row = docRow(page, doc.rowLabel);
            const viewHref = await row.locator('.wpwing_wcpdf_view_invoice').getAttribute('href');
            const previewHref = await row.locator('.wpwing_wcpdf_preview_html').getAttribute('href');
            expect(viewHref).toBeTruthy();
            expect(previewHref).toBeTruthy();

            const pdfResponse = await page.request.get(viewHref!);
            expect(pdfResponse.status()).toBe(200);
            expect(pdfResponse.headers()['content-type']).toContain('application/pdf');

            const htmlResponse = await page.request.get(previewHref!);
            expect(htmlResponse.status()).toBe(200);
            expect(htmlResponse.headers()['content-type']).toContain('text/html');

            if (doc.key === 'invoice') {
                await expect(page.locator('.wpwing-wcpdf-summary')).toContainText('Invoiced on:');
                await expect(page.locator('.wpwing-wcpdf-summary')).toContainText('Invoice:');
            }

            page.once('dialog', (dialog) => dialog.accept());
            await row.locator('.wpwing_wcpdf_cancel_invoice').click();
            await expect(page.getByText(doc.deletedNotice)).toBeAttached();

            await expect(docRow(page, doc.rowLabel).locator('.wpwing_wcpdf_create_invoice')).toBeVisible();
        });
    });
}
