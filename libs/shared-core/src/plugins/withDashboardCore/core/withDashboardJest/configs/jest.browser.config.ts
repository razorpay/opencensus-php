import path from 'path';
import { supportedExtensionsToResolveForJest } from '../constants';
import { DASHBOARD_ROOT } from '@src/constants';
import { moduleNameMapGenerator } from '../utils/moduleNameMapGenerator';
import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';
import { removeDir, createDir } from '@src/scripts';
import { writeFileSync } from 'fs';
import { WithDashboardJestConfigs, WithDashboardJestType } from '../types';
import { Config } from 'jest';

type withDashboardBrowserJestConfigType = (
  options: WithDashboardJestConfigs,
) => ReturnType<WithDashboardJestType>;

export const withDashboardBrowserJestConfig: withDashboardBrowserJestConfigType = (options) => {
  const { extendBrowserJestConfig, browserJestOptions } = options || {};
  const rootDir = path.resolve(process.cwd());
  const isVerbose = true;
  const isCI = process.env.CI === 'true';
  const maxWorkers = Number(process.env.MAX_WORKERS);
  const shard = process.env.SHARD;

  const isReportPortalEnabled =
    process.env.DISABLE_REPORT_PORTAL_INTEGRATION &&
    process.env.DISABLE_REPORT_PORTAL_INTEGRATION === 'false';

  const displayName = browserJestOptions.moduleName.includes('@libs/')
    ? browserJestOptions.moduleName.replace('@libs', '').replace('/', ' ').toUpperCase()
    : browserJestOptions.moduleName.toUpperCase().split('-').join(' ');

  if (!rootDir) {
    throw new Error('[@libs/shared-core] Unable to evaluate rootDir.');
  }

  process.env['JEST_BROWSER_MODULE_NAME'] = browserJestOptions.moduleName;

  const rootNodeModulesDir = `<rootDir>/${path.relative(rootDir, DASHBOARD_ROOT)}`;
  const jestAnalysisDir = `<rootDir>/.jest-analysis`;
  const actualJestAnalysisDir = path.resolve(process.cwd(), `./.jest-analysis`);

  const dashboardBrowserJestConfig: Partial<Config> = {
    rootDir,

    testEnvironment: 'jsdom',

    displayName,
    // The directory where Jest should output its coverage files
    coverageDirectory: jestAnalysisDir,

    // Automatically clear mock calls and instances between every test
    clearMocks: true,

    // An array of file extensions your modules use
    moduleFileExtensions: supportedExtensionsToResolveForJest,

    moduleDirectories: [
      path.relative(rootDir, `${DASHBOARD_ROOT}/node_modules`),
      path.relative(rootDir, './node_modules'),
    ],

    transform: {
      '\\.(js|ts|jsx|tsx)?$': `${rootNodeModulesDir}/libs/shared-core/dist/babelTransformer.jest.cjs.js`,
      '\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$': `${rootNodeModulesDir}/libs/shared-core/dist/fileTransformer.jest.cjs.js`,
    },

    moduleNameMapper: {
      '\\.(css|less|styl)$': `${rootNodeModulesDir}/node_modules/identity-obj-proxy`,
      ...moduleNameMapGenerator(browserJestOptions.moduleName, rootDir),
    },
    // An array of regexp pattern strings that are matched against all source file paths, matched files will skip transformation
    transformIgnorePatterns: [
      '/node_modules/(?!(?:.pnpm/)?(@razorpay|copy-anything|is-what|@table-library|uuid|node-fetch)).*/',
    ],

    verbose: isVerbose,
    collectCoverageFrom: [
      '**/*.{ts,tsx,js,jsx}',
      '!stories/**/*.{ts,tsx,js,jsx}',
      '!coverage/**/*.{ts,tsx,js,jsx}',
      '!entryBrowser.tsx',
      '!**/*.stories.{js,jsx,ts,tsx}',
      '!**/SupportSection.tsx',
      '!**/e2e/**/*.{ts,tsx,js,jsx}',
    ],
    noStackTrace: false,

    // @ts-ignore
    reporters: [
      'default',
      isCI && externalDeps['jest-sonar'],
      !Boolean(shard) &&
        isCI &&
        isReportPortalEnabled && [
          '@reportportal/agent-js-jest',
          {
            token: process.env.REPORT_PORTAL_TOKEN,
            endpoint: `${process.env.REPORT_PORTAL_HOST}/api/v1`,
            project: process.env.REPORT_PORTAL_PROJECT,
            launch: process.env.REPORT_PORTAL_LAUNCH_NAME || `${process.env.APP_NAME} Jest Tests`,
            logLaunchLink: true,
            debug: true,
            attributes: [
              {
                key: 'build',
                value: `jest.${process.env.COMMIT_ID}`,
              },
              {
                key: 'PR',
                value: `${process.env.PULL_REQUEST_NUMBER}`,
              },
              {
                key: 'Project',
                value: browserJestOptions.moduleName,
              },
            ],
            restClientConfig: {
              timeout: 200000,
            },
          },
        ],
      [
        externalDeps['jest-html-reporters'],
        {
          publicPath: `${jestAnalysisDir}/html-report`,
          filename: `index.html`,
          openReport: false,
          pageTitle: `${displayName} (Jest Report)${Boolean(shard) ? ` - Shard ${shard}` : ''}`,
          darkTheme: false,
          includeConsoleLog: true,
          // logoImgPath: "https://framerusercontent.com/images/CU1m0xFonUl76ZeaW0IdkQ0M.png",
        },
      ],
    ].filter(Boolean),

    globals: {
      __DEPLOYMENT_TYPE__: 'default',
    },
    collectCoverage: false, //TEMP
    coveragePathIgnorePatterns: [
      '/node_modules/',
      '__test__',
      '__tests__',
      'typings',
      '<rootDir>/e2e/',
      '<rootDir>/dist',
      '<rootDir>/build',
      '<rootDir>/node_modules',
      '<rootDir>/playwright.config.js',
      '<rootDir>/webpack.config.js',
      '<rootDir>/eslint.config.js',
      '<rootDir>/.babelrc.js',
      '<rootDir>/tsconfig.json',
      '<rootDir>/package.json',
    ],
    coverageReporters: [
      'json',
      'lcov',
      ...((Boolean(shard) ? [] : ['clover', 'text-summary']) as any),
    ],
    testMatch: ['**/__tests__/**/*.[jt]s?(x)', '**/?(*.)+(spec|test).[jt]s?(x)', '!**/mocks/**'],
    testPathIgnorePatterns: [
      '/node_modules/',
      '<rootDir>/e2e/',
      '<rootDir>/dist',
      '<rootDir>/build',
      '<rootDir>/node_modules',
    ],
    setupFilesAfterEnv: ['<rootDir>/src/services/test/setupTests.tsx'],
    globalSetup: '<rootDir>/src/services/test/global-setup.ts',
    testTimeout: 15000,
    maxWorkers: Boolean(maxWorkers) ? maxWorkers : '50%',
    logHeapUsage: isCI,
  };

  const consumerJestConfiguration = extendBrowserJestConfig?.(dashboardBrowserJestConfig);

  removeDir(actualJestAnalysisDir);
  createDir(actualJestAnalysisDir);

  if (shard && consumerJestConfiguration?.coverageThreshold) {
    if (shard === '1') {
      try {
        const thresholdFilePath = path.resolve(actualJestAnalysisDir, './coverage-threshold.json');

        writeFileSync(
          thresholdFilePath,
          JSON.stringify(consumerJestConfiguration.coverageThreshold, null, 2),
          'utf-8',
        );

        console.info(`[@libs/shared-core] Coverage threshold saved to ${thresholdFilePath}`);
      } catch (error) {
        console.log(error);
        throw new Error(
          `[@libs/shared-core] Failed to generate coverage threshold file for later processing.`,
        );
      }
    }
    // @ts-ignore
    consumerJestConfiguration.coverageThreshold = undefined;
  }

  return consumerJestConfiguration;
};
