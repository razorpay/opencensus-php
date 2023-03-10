const { devices } = require('@playwright/test');
const universePlaywrightConfig = require('@razorpay/universe-test/src/configs/e2e.web/playwright.config');
const { getBaseUrl } = require('./e2e/utils/config');

module.exports = {
  ...universePlaywrightConfig,
  testDir: 'e2e/suites',
  testMatch: ['**/?(*.)+(spec).[jt]s?(x)'],
  globalSetup: './e2e/setup/globalSetup',
  timeout: 200 * 1000,
  expect: {
    timeout: 10000,
  },
  use: {
    ...universePlaywrightConfig.use,
    baseURL: getBaseUrl(),
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'on-first-retry',
  },
  projects: [
    ...universePlaywrightConfig.projects,
    /* Test against mobile viewports. */
    {
      name: 'Mobile Chrome',
      use: { ...devices['Galaxy S8'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],
};
