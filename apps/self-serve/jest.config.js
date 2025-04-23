const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.SELF_SERVE,
  },
  extendBrowserJestConfig: (config) => {
    config.moduleNameMapper = {
      ...config.moduleNameMapper,
      '^apps/self-serve/src(/.*)$': '<rootDir>/src/$1',
    };

    config.coverageThreshold = {
      global: {
        statements: 51.39,
        branches: 37.58,
        functions: 40.29,
        lines: 52.16,
      },
    };

    config.testPathIgnorePatterns = [
      ...config.testPathIgnorePatterns,
      '/Refunds/',
      '/PaymentsDetails/',
    ];

    return config;
  },
});
