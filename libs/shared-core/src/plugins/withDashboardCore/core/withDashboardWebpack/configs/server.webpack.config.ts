import path from 'path';
import { Configuration, DefinePlugin, HotModuleReplacementPlugin, optimize } from 'webpack';
import {
  RestrictedConfig,
  WithDashboardWebpackConfigs,
  WithDashboardWebpackType,
} from '@src/plugins/withDashboardCore/core/withDashboardWebpack/types';
import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_ROOT } from '@src/constants';
import { dashboardSentryPlugin } from '@src/plugins/dashboardSentryPlugin';
import {
  DashboardNodeFederationPlugin,
  DashboardStreamingTargetPlugin,
} from '@src/plugins/DashboardFederationPlugin';
import { getCommonRules } from '@src/plugins/withDashboardCore/rules';
import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';
import { CONSUMER_APP_CONSTANTS, envConfig } from '@src/plugins/withDashboardCore/env';
import { getSentryMeta } from '@src/plugins/dashboardSentryPlugin/utils';
import { getAliasedDashboardModules } from '@src/plugins/withDashboardCore/utils/getAliasedDashboardModules';
import { supportedExtensionsToResolveForNode } from '@src/plugins/withDashboardCore/core/withDashboardWebpack/constants';

type withDashboardServerWebpackConfigType = (
  args: WithDashboardWebpackConfigs,
) => ReturnType<WithDashboardWebpackType>['browserConfig'];

export const withDashboardServerWebpackConfig: withDashboardServerWebpackConfigType = (args) => {
  return () => {
    const { serverBundlerOptions: options, extendServerWebpackConfig } = args;
    const WebpackBar = require(externalDeps['webpackbar']);
    const NodemonPlugin = require(externalDeps['nodemon-webpack-plugin']);
    const buildDir = path.resolve(process.cwd(), './build/server');

    const { APP_VERSION, isProd, isDev, isCI, STAGE, isWebpackWithSwc } = CONSUMER_APP_CONSTANTS;
    const isInAppDir = Boolean(process.cwd()?.split('/').at(-2) === 'apps');
    const isSentryEnabled =
      ['production', 'canary'].includes(STAGE!) && Boolean(APP_VERSION && options?.sentryConfig);

    if (isSentryEnabled) {
      if (!Boolean(options?.sentryConfig?.dsn))
        throw new Error('[@libs/shared-core] Sentry dsn is missing!');
      if (!Boolean(options?.sentryConfig?.project))
        throw new Error('[@libs/shared-core] Sentry project is missing!');
    }

    const isShell = [DASHBOARD_FEDERATED_MODULES.SHELL_SERVER].includes(options.moduleName);

    const sentryMeta =
      (isSentryEnabled &&
        getSentryMeta({
          env: {
            APP_VERSION,
            STAGE,
          },
          moduleName: options.moduleName,
          sentryProject: options?.sentryConfig?.project!,
        })) ||
      {};

    const extraMetaForConsumerApp = {
      ...CONSUMER_APP_CONSTANTS,
      ...sentryMeta,
      externalDeps,
    };

    const consumerAppServerConfig =
      extendServerWebpackConfig?.(
        {
          resolve: {
            alias: {},
          },
          entry: [],
          module: {
            rules: [],
          },
          plugins: [],
        },
        // @ts-ignore
        extraMetaForConsumerApp,
      ) || ({} as RestrictedConfig);

    const finalConfig: Configuration = {
      bail: true,
      mode: isDev ? 'development' : 'production',
      name: `${options.moduleName}.server`,
      entry: consumerAppServerConfig.entry,
      stats: isDev ? 'minimal' : 'verbose',
      target: 'node',
      node: {
        __dirname: false,
      },
      devtool:
        !isProd && isSentryEnabled
          ? 'hidden-source-map' // For Prod Env (Only used for sentry ref)
          : isDev
          ? 'eval' // For balanced HMR compile time
          : 'source-map', // For better debugging in devstack env
      externals: {
        '@sentry/profiling-node': 'commonjs @sentry/profiling-node',
      },
      resolve: {
        modules: ['node_modules'],
        // support code sharing capabilities based on extension. A web(.desktop.js, .web.js) and native app(.native.js handled by metro bundler) codebase can co-exist together
        extensions: supportedExtensionsToResolveForNode,
        symlinks: true,
        alias: {
          ...getAliasedDashboardModules({
            moduleName: options.moduleName,
          }),
        },
      },
      parallelism: isDev ? 100 : 500,
      infrastructureLogging: {
        level: isDev ? 'error' : 'verbose',
      },
      cache: isDev
        ? {
            type: 'filesystem',
            name: !isDev ? 'server-production' : 'server-development',
            buildDependencies: {
              config: [__filename],
            },
            cacheDirectory: path.resolve(
              DASHBOARD_ROOT,
              `./node_modules/.dashboard-core/cache/webpack/${options.moduleName}`,
            ),
          }
        : false,
      output: {
        asyncChunks: true,
        hashFunction: 'xxhash64',
        clean: true,
        hotUpdateChunkFilename: isDev ? `hmr.${options.moduleName}.[id].[fullhash].js` : undefined,
        path: buildDir,
        publicPath: process.env.UNIVERSE_PUBLIC_SERVER_ASSETS_URL,
        filename: 'main.js',
        uniqueName: options.moduleName,
        libraryTarget: 'commonjs-module',
      },
      plugins: [
        new DefinePlugin({
          __STAGE__: JSON.stringify(process.env.STAGE),
          __APP_VERSION__: JSON.stringify(APP_VERSION),
          __BUILD_MODE__: JSON.stringify(envConfig.buildMode),
          __IS_WEBPACK_WITH_SWC__: JSON.stringify(options.isWebpackWithSwc ?? isWebpackWithSwc),
        }),
        new optimize.LimitChunkCountPlugin({
          maxChunks: 1,
        }),
        isDev ? new HotModuleReplacementPlugin() : null,
        !isCI &&
          new WebpackBar({
            color: 'green',
            name: `[@libs/shared-core] ${options.moduleName}`,
            profile: false,
          }),
        isDev
          ? new NodemonPlugin({
              verbose: true,
              script: `${buildDir}/main.js`,
              watch: buildDir,
            })
          : null,
        isSentryEnabled &&
          dashboardSentryPlugin({
            moduleName: options.moduleName,
            sentryDSN: options.sentryConfig!.dsn,
            sentryProject: options.sentryConfig!.project,
          }),
        Boolean(options.nodeFederationConfig) &&
          new DashboardNodeFederationPlugin({
            name: options.moduleName,
            exposedDir: options.nodeFederationConfig!.exposedDir,
            remotes: options.nodeFederationConfig!.remotes,
          }),
        Boolean(options.streamFederationConfig) &&
          new DashboardStreamingTargetPlugin({
            name: options.moduleName,
            exposedDir: options.streamFederationConfig!.exposedDir,
            remotes: options.streamFederationConfig!.remotes,
          }),
        ...(consumerAppServerConfig.plugins || []),
      ].filter(Boolean),
      module: {
        rules: [
          ...getCommonRules({
            isDev,
            isInAppDir,
            isNodeApp: true,
            isShell,
            isWebpackWithSwc: options.isWebpackWithSwc ?? isWebpackWithSwc,
            moduleName: options.moduleName,
          }),
          ...(consumerAppServerConfig.module?.rules || []), // Add rules if any (by consumer app)
        ],
      },
    };

    return finalConfig;
  };
};
