const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

module.exports = withDashboardCore({
  playwrightOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.{{ integratedAppName }},
  },
  extendPlaywrightConfig: (config) => {
    config.testDir = './e2e/suites';
    return config;
  },
});
