import type { DASHBOARD_FEDERATED_MODULES } from '../../constants/DASHBOARD_FEDERATED_MODULES';
import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '../../configs';
import { getSentryMeta } from './utils';
import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';

const { sentryWebpackPlugin } = require(externalDeps['@sentry/webpack-plugin']);

export const dashboardSentryPlugin = ({
  moduleName,
  sentryDSN,
  sentryProject,
}: {
  moduleName: DASHBOARD_FEDERATED_MODULES;
  sentryProject: string;
  sentryDSN: string;
}) => {
  const APP_VERSION = process.env.VERSION;

  if (!APP_VERSION) {
    throw new Error('[@libs/shared-core] dashboardSentryPlugin should only run on CI');
  }

  const STAGE = process.env.STAGE;

  if (!STAGE) {
    throw new Error('[@libs/shared-core] unable to identify environment. STAGE is undefined!');
  }

  if (!sentryDSN) {
    throw new Error('[@libs/shared-core] sentryDSN is missing!');
  }

  if (!sentryProject) {
    throw new Error('[@libs/shared-core] sentryProject is missing!');
  }

  const targetModuleConfig = DASHBOARD_FEDERATED_MODULE_CONFIGS[moduleName];

  const { sentryAppVersion } = getSentryMeta({
    env: { APP_VERSION, STAGE },
    moduleName,
    sentryProject,
  });

  const buildDir = `${process.cwd()}/build/${targetModuleConfig.buildType}`;

  return sentryWebpackPlugin({
    telemetry: false,
    org: 'rzp',
    project: sentryProject,
    debug: false,
    authToken:
      typeof process.env.SENTRY_AUTH_TOKEN === 'string'
        ? process.env.SENTRY_AUTH_TOKEN
        : JSON.stringify(process.env.SENTRY_AUTH_TOKEN),
    sourcemaps: {
      assets: [`${buildDir}/**/*`],
      filesToDeleteAfterUpload: [`${buildDir}/**/*.map`],
      ignore: ['node_modules', 'webpack.config.js', 'webpack.server.js', 'webpack.client.js'],
    },
    moduleMetadata: {
      dsn: sentryDSN,
      project: sentryProject,
      buildType: targetModuleConfig.buildType,
      moduleName,
    },
    headers: {
      'x-dashboard-module': moduleName.split('_').join('-'),
    },
    errorHandler: (err: Error) => console.log('[@libs/shared-core]', err),
    release: {
      dist: APP_VERSION,
      deploy: {
        env: STAGE!,
        name: moduleName.split('_').join('-'),
      },
      name: sentryAppVersion,
      inject: true,
      create: true,
    },
  });
};
