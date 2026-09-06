const { defineConfig, devices } = require('@playwright/test');

const STORAGE_STATE = './tests/e2e/.auth/admin.json';

module.exports = defineConfig({
    testDir: './tests/e2e/specs',
    fullyParallel: true,
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
