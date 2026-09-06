import { chromium, FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const STORAGE_STATE = path.join(__dirname, '.auth/admin.json');

export default async function globalSetup(config: FullConfig) {
    const baseURL = config.projects[0]?.use?.baseURL ?? process.env.BASE_URL ?? 'https://pdf-invoice.local';

    const browser = await chromium.launch();
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    await page.goto(`${baseURL}/wp-login.php`);
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(`${baseURL}/wp-admin/**`);

    fs.mkdirSync(path.dirname(STORAGE_STATE), { recursive: true });
    await context.storageState({ path: STORAGE_STATE });
    await browser.close();
}
