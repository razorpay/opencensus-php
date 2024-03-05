const playwrightBaseConfig = require('../../playwright.config');
const { getProjects } = require('../../playwright/utils/config');

module.exports = {
  ...playwrightBaseConfig,
  testDir: 'e2e/suites',
  // re-setting it to undefined to avoid running globalSetup
  globalSetup: undefined,
  projects: getProjects({ projectType: 'Self Serve' }),
};
