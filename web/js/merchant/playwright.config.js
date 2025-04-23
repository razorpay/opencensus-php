const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

// dummy 1
module.exports = withDashboardCore({
  playwrightOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD,
  },
  extendPlaywrightConfig: (config) => {
    config.testDir = '../../e2e/suites';
    return config;
  },
});
