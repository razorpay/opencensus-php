// For a detailed explanation regarding each configuration property, visit:
// https://jestjs.io/docs/en/configuration.html
const path = require('path');

module.exports = {
  // Automatically clear mock calls and instances between every test
  clearMocks: true,

  // The directory where Jest should output its coverage files
  coverageDirectory: 'coverage',
  // An array of file extensions your modules use
  moduleFileExtensions: ['web.js', 'js', 'json', 'jsx', 'ts', 'tsx'],

  // An array of directory names to be searched recursively up from the requiring module's location
  moduleDirectories: ['js', 'node_modules', 'js/common/services/test', __dirname],

  // A map from regular expressions to module names or to arrays of module names that allow to stub out resources with a single module
  moduleNameMapper: {
    '\\.(css|less|styl)$': 'identity-obj-proxy',
    '^assets/(.*)': 'css/assets/$1',
    '^shell/commonStore': path.resolve(__dirname, './js/merchant/commonStore'),
    '^shell/SpiltzServiceContext': path.resolve(
      __dirname,
      './js/common/splitz/context/SplitzContextProvider',
    ),
    '^shell/I18Context': path.resolve(__dirname, './js/common/i18/I18ServiceProvider'),
    '^shell/components/ShowWhen': path.resolve(
      __dirname,
      './js/merchant_common/components/SharedShowWhen',
    ),
    '^shell/deprecated/withRouter': path.resolve(__dirname, './js/common/deprecated/withRouter'),
    '@razorpay/i18nify-js/currency': path.resolve(
      __dirname,
      'node_modules/@razorpay/i18nify-js/lib/esm/currency/index.js',
    ),
    '@razorpay/i18nify-js/phoneNumber': path.resolve(
      __dirname,
      'node_modules/@razorpay/i18nify-js/lib/esm/phoneNumber/index.js',
    ),
  },

  // A map from regular expressions to paths to transformers
  // transform: {
  //   '\\.(js|ts|jsx|tsx)?$': '../jestTransform.js',
  // },
  transform: {
    '^.+\\.stories\\.[jt]sx?$': '@storybook/addon-storyshots/injectFileName',
    '^.+\\.[jt]sx?$': '../jestTransform.js',
    '\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$':
      '../tools/fileTransformer.js',
  },
  // An array of regexp pattern strings that are matched against all source file paths, matched files will skip transformation
  transformIgnorePatterns: [
    '/node_modules/(?!(?:.pnpm/)?(@commander|@razorpay|copy-anything|is-what|@table-library)).*/',
  ],
  // The root directory that Jest should scan for tests and modules within
  rootDir: 'js',

  // A list of paths to directories that Jest should use to search for files in
  // roots: ['js/', 'Storyshots.test.js'],

  // A list of paths to modules that run some code to configure or set up the testing framework before each test
  setupFilesAfterEnv: ['<rootDir>/common/services/test/setupTests.js'],

  reporters: [
    ['jest-silent-reporter', { useDots: true, showPaths: true }],
    'jest-sonar',
    ...(process.env.CI === 'true' && !process.env.DISABLE_REPORT_PORTAL_INTEGRATION
      ? [
          [
            '@reportportal/agent-js-jest',
            {
              token: process.env.REPORT_PORTAL_TOKEN,
              endpoint: `${process.env.REPORT_PORTAL_HOST}/api/v1`,
              project: process.env.REPORT_PORTAL_PROJECT,
              launch: process.env.REPORT_PORTAL_LAUNCH_NAME,
              logLaunchLink: true,
              debug: true,
              attributes: [
                {
                  key: 'build',
                  value: `${process.env.COMMIT_ID}`,
                },
              ],
            },
          ],
        ]
      : []),
  ],

  // Indicates whether the coverage information should be collected while executing the test
  collectCoverage: true,

  // An object that configures minimum threshold enforcement for coverage results

  coverageThreshold: {
    global: {
      statements: 47,
      branches: 35,
      functions: 38,
      lines: 48,
    },
    './js/merchant/views/PartnerDashboard': {
      statements: 60.52,
      branches: 57.26,
      functions: 52.45,
      lines: 52.45,
    },
    // './js/merchant/views/onboarding/': {
    //   statements: 71,
    //   branches: 69,
    //   functions: 55,
    //   lines: 69,
    // },
    './js/merchant/views/Transactions/v1/Payments/': {
      statements: 81,
      branches: 74,
      functions: 81,
      lines: 81,
    },
  },

  // An array of glob patterns indicating a set of files for which coverage information should be collected
  // collectCoverageFrom: ['**/views/onboarding/**/*.{js,jsx,ts,tsx}'],

  // All imported modules in your tests should be mocked automatically
  // automock: false,

  // Stop running tests after `n` failures
  // bail: 0,

  // The directory where Jest should store its cached dependency information
  // cacheDirectory: "/private/var/folders/j9/_gm_d82j2q71v20yv2mx_xrm0000gn/T/jest_dx",

  // An array of regexp pattern strings used to skip coverage collection
  coveragePathIgnorePatterns: ['/node_modules/', '__test__', '__tests__', 'typings'],

  // Indicates which provider should be used to instrument code for coverage
  // coverageProvider: "babel",

  // A list of reporter names that Jest uses when writing coverage reports
  coverageReporters: ['clover', 'json', 'lcov', 'text-summary'],

  // A path to a custom dependency extractor
  // dependencyExtractor: undefined,

  // Make calling deprecated APIs throw helpful error messages
  // errorOnDeprecated: false,

  // Force coverage collection from ignored files using an array of glob patterns
  // forceCoverageMatch: [],

  // A path to a module which exports an async function that is triggered once before all test suites
  globalSetup: '<rootDir>/common/services/test/global-setup.js',

  // A path to a module which exports an async function that is triggered once after all test suites
  // globalTeardown: undefined,

  // A set of global variables that need to be available in all test environments
  // globals: {},

  // The maximum amount of workers used to run your tests. Can be specified as % or a number. E.g. maxWorkers: 10% will use 10% of your CPU amount + 1 as the maximum worker number. maxWorkers: 2 will use a maximum of 2 workers.
  // maxWorkers: "50%",

  // An array of regexp pattern strings, matched against all module paths before considered 'visible' to the module loader
  // modulePathIgnorePatterns: [],

  // Activates notifications for test results
  // notify: false,

  // An enum that specifies notification mode. Requires { notify: true }
  // notifyMode: "failure-change",

  // Run tests from one or more projects
  // projects: undefined,

  // Automatically reset mock state between every test
  // resetMocks: false,

  // Reset the module registry before running each individual test
  // resetModules: false,

  // A path to a custom resolver
  // resolver: undefined,

  // Automatically restore mock state between every test
  // restoreMocks: false,

  // Allows you to use a custom runner instead of Jest's default test runner
  // runner: "jest-runner",

  // The paths to modules that run some code to configure or set up the testing environment before each test
  // setupFiles: [],

  // The number of seconds after which a test is considered as slow and reported as such in the results.
  // slowTestThreshold: 5,

  // A list of paths to snapshot serializer modules Jest should use for snapshot testing
  // snapshotSerializers: [],

  // The test environment that will be used for testing
  // testEnvironment: "jest-environment-jsdom",
  // testEnvironment: 'jsdom',

  // Adds a location field to test results
  // testLocationInResults: false,

  // The glob patterns Jest uses to detect test files
  testMatch: ['**/__tests__/**/*.[jt]s?(x)', '**/?(*.)+(spec|test).[tj]s?(x)', '!**/mocks/**'],

  // An array of regexp pattern strings that are matched against all test paths, matched tests are skipped
  // testPathIgnorePatterns: [
  //   "/node_modules/"
  // ],

  // The regexp pattern or array of patterns that Jest uses to detect test files
  // testRegex: '(/test/.*|\\.(test|spec))\\.(ts|tsx|js)$',

  // This option allows the use of a custom results processor
  // testResultsProcessor: undefined,

  // This option allows use of a custom test runner
  // testRunner: "jasmine2",

  // This option sets the URL for the jsdom environment. It is reflected in properties such as location.href
  // testURL: "http://localhost",

  // Setting this value to "fake" allows the use of fake timers for functions such as "setTimeout"
  // timers: "real",

  // An array of regexp pattern strings that are matched against all modules before the module loader will automatically return a mock for them
  // unmockedModulePathPatterns: undefined,

  // Indicates whether each individual test should be reported during the run
  // verbose: undefined,

  // An array of regexp patterns that are matched against all source file paths before re-running tests in watch mode
  // watchPathIgnorePatterns: [],

  // Whether to use watchman for file crawling
  // watchman: true,
  testRunner: 'jest-circus/runner',
  testTimeout: 10000,
};
