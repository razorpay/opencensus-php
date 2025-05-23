const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.POS,
  },
  extendBrowserJestConfig: (config) => {
    config.moduleNameMapper = {
      ...config.moduleNameMapper,
      '^apps/pos/src(/.*)$': '<rootDir>/src/$1',
    };

    config.coverageThreshold = {
      global: {
        statements: 50,
        branches: 50,
        functions: 50,
        lines: 50,
      },
    };

    config.coveragePathIgnorePatterns = [
      ...config.coveragePathIgnorePatterns,
      '<rootDir>/new-coverage.js',
    ];

    return config;
  },
});
