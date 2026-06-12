const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    use: {
        baseURL: process.env.BASE_URL || 'http://pdf-invoice.local:1122',
        screenshot: 'only-on-failure',
    },
    reporter: 'list',
});
