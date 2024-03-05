const universeJestConfig = require('@razorpay/universe-cli/jest.config');
const nodeModulesRootDir = '<rootDir>/../..';

module.exports = {
  ...universeJestConfig,
  transform: {
    '\\.(js|ts|jsx|tsx)?$': '../jest-transformer.js',
  },
  transformIgnorePatterns: ['/node_modules/(?!(@razorpay/blade)|uuid/)'],
  moduleNameMapper: {
    // Since jest doesn't know how to resolve these static assets, we mock them
    '\\.(css)$': `${nodeModulesRootDir}/../jest-styleMock.js`,
    '\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga|docx)$': `${nodeModulesRootDir}/../jest-fileMock.js`,
    '@razorpay/blade/components': `${nodeModulesRootDir}/../node_modules/@razorpay/blade/build/components/index.development.web.js`,
    '@razorpay/blade/utils': `${nodeModulesRootDir}/../node_modules/@razorpay/blade/build/utils/index.development.web.js`,
    '@razorpay/blade/tokens': `${nodeModulesRootDir}/../node_modules/@razorpay/blade/build/tokens/index.development.web.js`,
    '^@dashboard/shared-utils(/.*)?$': `${nodeModulesRootDir}/../libs/shared-utils/src$1`,
    '^shell/commonStore': `${nodeModulesRootDir}/../web/js/merchant/commonStore/index`,
    '^merchant(/.*)?$': `${nodeModulesRootDir}/../web/js/merchant$1`,
    '^common(/.*)?$': `${nodeModulesRootDir}/../web/js/common$1`,
  },
  collectCoverage: true,
  collectCoverageFrom: ['**/*.{ts,tsx,js,jsx}', '!coverage/**/*.{ts,tsx,js,jsx}'],
  coverageThreshold: {
    global: {
      statements: 1,
      branches: 0.09,
      functions: 1,
      lines: 1,
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
