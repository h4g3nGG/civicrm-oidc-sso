const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: '.',
  testMatch: 'oidc-flow.spec.js',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: 'line',
  outputDir: '/results',
  use: {
    baseURL: 'https://civicrm',
    ignoreHTTPSErrors: true,
    screenshot: 'only-on-failure',
    trace: 'off',
  },
});
