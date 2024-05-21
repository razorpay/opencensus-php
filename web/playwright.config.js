const { getProjects } = require('../playwright/utils/config');
const playwrightBaseConfig = require('../playwright.config');

module.exports = {
  ...playwrightBaseConfig,
  testDir: 'e2e/suites',
  // re-setting it to undefined to avoid running globalSetup.
  globalSetup: undefined,
  projects: getProjects({ projectType: 'Web Merchant' }),
};
