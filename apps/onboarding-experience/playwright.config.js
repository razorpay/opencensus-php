const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

module.exports = withDashboardCore({
  playwrightOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.ONBOARDING_EXPERIENCE,
  },
  extendPlaywrightConfig: (config) => {
    config.testDir = './e2e/suites';
    return config;
  },
});
