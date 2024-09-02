const path = require('path');

const { getBaseUrl, getProjects, getReporter } = require('./playwright/utils/config');

const isCI = process.env.CI;

module.exports = {
  testDir: 'playwright/e2e/suites',
  testMatch: ['**/?(*.)+(spec).[jt]s?(x)'],
  globalSetup: path.resolve(__dirname, './playwright/setup/globalSetup'),
  retries: isCI ? 2 : 0,
  timeout: 6 * 60 * 1000,
  workers: isCI ? 2 : 4,
  reporter: getReporter(),
  fullyParallel: true,
  forbidOnly: !!isCI,
  // This includes retries also in the maxFailures count (45/3 = 15 unique failures - worst case scenario)
  // maxFailures: isCI ? 45 : undefined,
  expect: {
    timeout: 30 * 1000,
  },
  use: {
    baseURL: getBaseUrl(),
    headless: !!isCI,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'on-first-retry',
    actionTimeout: 30 * 1000,
    permissions: ['clipboard-read', 'clipboard-write', 'accessibility-events'],
    contextOptions: {
      strictSelectors: true,
    },
  },
  projects: getProjects({ projectType: 'Login' }),
};
