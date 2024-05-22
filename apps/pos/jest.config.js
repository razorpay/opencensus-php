const universeJestConfig = require('@razorpay/universe-cli/jest.config');
const nodeModulesRootDir = '<rootDir>/../..';

module.exports = {
  ...universeJestConfig,
  clearMocks: true,
  transform: {
    '\\.(js|ts|jsx|tsx)?$': '../jest-transformer.js',
  },
  transformIgnorePatterns: [
    '/node_modules/(?!(?:.pnpm/)?(@commander|@razorpay|copy-anything|is-what|@table-library)).*/',
  ],
  moduleNameMapper: {
    // Since jest doesn't know how to resolve these static assets, we mock them
    '\\.(css|styl)$': `${nodeModulesRootDir}/../jest-styleMock.js`,
    '\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga|docx)$': `${nodeModulesRootDir}/../jest-fileMock.js`,
    // these are needed to reference node_modules from the root of the project
    '^shell/commonStore': `${nodeModulesRootDir}/../web/js/merchant/commonStore/index`,
    '^shell/components/ShowWhen': `${nodeModulesRootDir}/../web/js/merchant_common/components/SharedShowWhen`,
    '^shell/deprecated/withRouter': `${nodeModulesRootDir}/../web/js/common/deprecated/withRouter`,
    '^shell/SpiltzServiceContext': `${nodeModulesRootDir}/../web/js/common/splitz/context/SplitzContextProvider`,
    '^shell/I18Context': `${nodeModulesRootDir}/../web/js/common/i18/I18ServiceProvider`,
    '^apps/pos/src(/.*)$': '<rootDir>/$1',
    '^@dashboard/shared-utils/(.*)': `${nodeModulesRootDir}/../libs/shared-utils/src/$1`,
    '^@dashboard/shared-utils$': `${nodeModulesRootDir}/../libs/shared-utils/src/index`,
    '^@dashboard/shared-ui(.*)$': `${nodeModulesRootDir}/../libs/shared-ui/src$1`,
    '^merchant(/.*)?$': `${nodeModulesRootDir}/../web/js/merchant$1`,
    '^merchant_common(/.*)?$': `${nodeModulesRootDir}/../web/js/merchant_common$1`,
    '^common(/.*)?$': `${nodeModulesRootDir}/../web/js/common$1`,
  },
  collectCoverage: true,
  collectCoverageFrom: [
    '**/*.{ts,tsx,js,jsx}',
    '!stories/**/*.{ts,tsx,js,jsx}',
    '!coverage/**/*.{ts,tsx,js,jsx}',
    '!entryBrowser.tsx',
    '!**/*.stories.{js,jsx,ts,tsx}',
    '!**/SupportSection.tsx',
    '!**/e2e/**/*.{ts,tsx,js,jsx}',
  ],
  coverageThreshold: {
    global: {
      statements: 41,
      branches: 41,
      functions: 30,
      lines: 41.5,
    },
  },
  globalSetup: '<rootDir>/services/test/global-setup.ts',
  moduleDirectories: [
    'node_modules',
    'src/services/test', // a utility folder
    __dirname, // the root directory
    'src',
  ],
  rootDir: 'src',
  setupFilesAfterEnv: ['<rootDir>/services/test/setupTests.tsx'],
  globals: {
    __DEPLOYMENT_TYPE__: 'default',
  },
  modulePathIgnorePatterns: ['.*e2e.*'],
};
