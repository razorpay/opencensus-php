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
        statements: 66.66,
        branches: 53.86,
        functions: 61.66,
        lines: 67.4,
      },
    };

    config.globalSetup = '<rootDir>/../common/services/test/global-setup.js';

    return config;
  },
});
