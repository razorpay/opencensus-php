const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');
const path = require('path');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.NEWAUTH_DASHBOARD,
  },
  extendBrowserJestConfig: (config) => {
    config.moduleNameMapper = {
      ...config.moduleNameMapper,
      '^test-utils': path.resolve(__dirname, '../common/services/test/test-utils.tsx'),
    };

    config.setupFilesAfterEnv = ['<rootDir>/../common/services/test/setupTests.js'];

    config.coverageThreshold = {
      global: {
        statements: 8.5,
        branches: 6.23,
        functions: 9.94,
        lines: 8.53,
      },
    };

    config.globalSetup = '<rootDir>/../common/services/test/global-setup.js';

    return config;
  },
});
