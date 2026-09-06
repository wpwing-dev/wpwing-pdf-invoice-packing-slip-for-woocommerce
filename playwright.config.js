const { defineConfig, devices } = require('@playwright/test');

const STORAGE_STATE = './tests/e2e/.auth/admin.json';

module.exports = defineConfig({
    testDir: './tests/e2e/specs',
    // The dev/e2e WordPress container runs on a tight memory limit
    // (docker-compose.yml), and PDF generation (dompdf) is memory-hungry -
    // concurrent workers reliably push it into an unresponsive OOM state.
    workers: 1,
    // The stack is resource-constrained enough that a single test can flake
    // under load from tests that ran just before it; one retry absorbs that.
    retries: 1,
    reporter: 'list',
    globalSetup: require.resolve('./tests/e2e/global-setup.ts'),
    use: {
        baseURL: process.env.BASE_URL || 'https://pdf-invoice.local',
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: STORAGE_STATE,
            },
        },
    ],
});
