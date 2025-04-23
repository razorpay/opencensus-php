const { withDashboardCore } = require('@libs/shared-core');
const path = require('path');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: '@libs/web-nexus/common',
  },
  extendBrowserJestConfig: (config) => {
    config.moduleNameMapper = {
      ...config.moduleNameMapper,
      '^test-utils': path.resolve(__dirname, './services/test/test-utils.tsx'),
    };

    config.setupFilesAfterEnv = ['<rootDir>/services/test/setupTests.js'];

    config.coverageThreshold = {
      global: {
        statements: 18,
        branches: 10.43,
        functions: 18.03,
        lines: 18.1,
      },
    };

    config.globalSetup = '<rootDir>/services/test/global-setup.js';

    return config;
  },
});
