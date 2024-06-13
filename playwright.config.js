const path = require('path');
const universePlaywrightConfig = require('@razorpay/universe-cli/e2e.web');

const { getBaseUrl, getProjects, getReporter } = require('./playwright/utils/config');

const isCI = process.env.CI;

module.exports = {
  ...universePlaywrightConfig,
  testDir: 'playwright/e2e/suites',
  testMatch: ['**/?(*.)+(spec).[jt]s?(x)'],
  globalSetup: path.resolve(__dirname, './playwright/setup/globalSetup'),
  retries: isCI ? 2 : 0,
  timeout: 6 * 60 * 1000,
  workers: isCI ? 2 : 4,
  reporter: getReporter(),
  fullyParallel: true,
  expect: {
    timeout: 30 * 1000,
  },
  use: {
    ...universePlaywrightConfig.use,
    baseURL: getBaseUrl(),
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'on-first-retry',
    actionTimeout: 30 * 1000,
    permissions: ['clipboard-read', 'clipboard-write', 'accessibility-events'],
  },
  projects: getProjects({ projectType: 'Login' }),
};
