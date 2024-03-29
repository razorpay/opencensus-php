const universeJestConfig = require('@razorpay/universe-cli/jest.config');
const nodeModulesRootDir = '<rootDir>/../..';

module.exports = {
  ...universeJestConfig,
  transform: {
    '\\.(js|ts|jsx|tsx)?$': '../jest-transformer.js',
  },
  transformIgnorePatterns: ['/node_modules/(?!(@razorpay/blade)|uuid|@table-library/)'],
  moduleNameMapper: {
    // Since jest doesn't know how to resolve these static assets, we mock them
    '\\.(css)$': `${nodeModulesRootDir}/../jest-styleMock.js`,
    '\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga|docx)$': `${nodeModulesRootDir}/../jest-fileMock.js`,
    '^@dashboard/shared-utils(/.*)?$': `${nodeModulesRootDir}/../libs/shared-utils/src$1`,
    '^@dashboard/shared-ui(/.*)?$': `${nodeModulesRootDir}/../libs/shared-ui/src$1`,
    '^merchant(/.*)?$': `${nodeModulesRootDir}/../web/js/merchant$1`,
    '^merchant_common(/.*)?$': `${nodeModulesRootDir}/../web/js/merchant_common$1`,
    '^common(/.*)?$': `${nodeModulesRootDir}/../web/js/common$1`,
  },
  collectCoverage: true,
  collectCoverageFrom: [
    '**/*.{ts,tsx,js,jsx}',
    '!stories/**/*.{ts,tsx,js,jsx}',
    '!coverage/**/*.{ts,tsx,js,jsx}',
    '!**/*.stories.{js,jsx,ts,tsx}',
  ],
  coverageThreshold: {
    global: {
      statements: 15,
      branches: 6,
      functions: 15,
      lines: 15,
    },
  },
  moduleDirectories: [
    'node_modules',
    '../../node_modules',
    'src/services/test', // a utility folder
    __dirname, // the root directory
    'src',
  ],
  rootDir: 'src',
  setupFilesAfterEnv: ['<rootDir>/services/test/setupTests.ts'],
  globals: {
    __DEPLOYMENT_TYPE__: 'default',
  },
};
