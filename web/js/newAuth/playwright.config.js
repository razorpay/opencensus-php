const { withDashboardCore } = require('@libs/shared-core');

module.exports = withDashboardCore({
  playwrightOptions: {
    moduleName: 'Login',
  },
  extendPlaywrightConfig: (config) => {
    config.testDir = 'e2e/suites';
    return config;
  },
});
