const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    timeout: 30000,
    expect: {
        timeout: 10000,
    },
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: 'http://127.0.0.1:8010',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    globalSetup: require.resolve('./tests/e2e/global-setup.cjs'),
    webServer: {
        command: 'node tests/e2e/serve.cjs',
        url: 'http://127.0.0.1:8010',
        reuseExistingServer: false,
        timeout: 120000,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
