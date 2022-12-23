const universePlaywrightConfig = require('@razorpay/universe-test/src/configs/e2e.web/playwright.config');
const { getStorageStatePath } = require('./e2e/setup/storageState');
const { getBaseUrl } = require('./e2e/utils/config');
const { devices } = require('@playwright/test');

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
    storageState: getStorageStatePath(),
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
      },
    },
  ],
};
