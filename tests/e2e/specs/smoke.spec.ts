import { test, expect } from '../fixtures';

test('admin can log in and see the seeded orders', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    await expect(page.getByRole('heading', { name: 'Orders', exact: true })).toBeVisible();
    await expect(page.locator('.wp-list-table tbody tr')).not.toHaveCount(0);
});
