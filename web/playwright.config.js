const universePlaywrightConfig = require('@razorpay/universe-test/src/configs/e2e.web/playwright.config');

const { getBaseUrl, getProjects, getReporter } = require('./e2e/utils/config');

const isCI = process.env.CI;
module.exports = {
  ...universePlaywrightConfig,
  testDir: 'e2e/suites',
  testMatch: ['**/?(*.)+(spec).[jt]s?(x)'],
  globalSetup: './e2e/setup/globalSetup',
  retries: isCI ? 1 : 0,
  timeout: 6 * 60 * 1000,
  workers: isCI ? 1 : 4,
  reporter: getReporter(),
  expect: {
    timeout: 30 * 1000,
  },
  use: {
    ...universePlaywrightConfig.use,
    baseURL: getBaseUrl(),
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'on-first-retry',
  },
  projects: getProjects(),
};
