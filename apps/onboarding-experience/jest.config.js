const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.ONBOARDING_EXPERIENCE,
  },
  extendBrowserJestConfig: (config) => {
    config.coverageThreshold = {
      global: {
        statements: 0,
        branches: 0,
        functions: 0,
        lines: 0,
      },
    };

    config.setupFilesAfterEnv = ['<rootDir>/src/services/test/jest-setup.ts'];

    return config;
  },
});
