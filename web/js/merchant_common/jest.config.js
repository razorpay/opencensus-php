const { withDashboardCore } = require('@libs/shared-core');
const path = require('path');

module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: '@libs/web-nexus/merchant',
  },
  extendBrowserJestConfig: (config) => {
    config.moduleNameMapper = {
      ...config.moduleNameMapper,
      '^test-utils': path.resolve(__dirname, './jest-utils/index.tsx'),
    };

    config.setupFilesAfterEnv = ['<rootDir>/jest-utils/jest.setup.tsx'];

    config.coverageThreshold = {
      global: {
        statements: 61.18,
        branches: 48.35,
        functions: 54.94,
        lines: 61.73,
      },
    };

    config.globalSetup = '<rootDir>/../common/services/test/global-setup.js';

    return config;
  },
});
