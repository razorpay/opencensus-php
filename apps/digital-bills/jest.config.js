const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.DIGITAL_BILLS,
  },
  extendBrowserJestConfig: (config) => {
    config.coverageThreshold = {
      global: {
        statements: 41,
        branches: 40,
        functions: 33.5,
        lines: 41.5,
      },
    };

    config.setupFilesAfterEnv = ['<rootDir>/src/services/test/jest-setup.js'];

    return config;
  },
});
