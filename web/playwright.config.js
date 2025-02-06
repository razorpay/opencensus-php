const { getProjects } = require('../playwright/utils/config');
const playwrightBaseConfig = require('../playwright.config');

module.exports = {
  ...playwrightBaseConfig,
  testDir: 'e2e/suites',
  // re-setting it to undefined to avoid running globalSetup
  globalSetup: undefined,
  projects: getProjects({ projectType: 'Web Merchant' }),
  use: {
    ...playwrightBaseConfig.use,
    actionTimeout: 30 * 1000,
    permissions: ['clipboard-read', 'clipboard-write', 'accessibility-events'],
  },
};
