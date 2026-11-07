const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: '.',
  testMatch: process.env.E2E_TEST_MATCH || 'oidc-flow.spec.js',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: 'line',
  outputDir: '/results',
  use: {
    baseURL: process.env.E2E_BASE_URL || 'https://civicrm',
    ignoreHTTPSErrors: true,
    screenshot: 'only-on-failure',
    trace: 'off',
  },
});
