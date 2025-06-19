import path from 'path';
import { Configuration, DefinePlugin, IgnorePlugin, ProvidePlugin } from 'webpack';
import {
  RestrictedConfig,
  WithDashboardWebpackConfigs,
  WithDashboardWebpackType,
} from '@src/plugins/withDashboardCore/core/withDashboardWebpack/types';
import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_ROOT } from '@src/constants';
import { dashboardSentryPlugin } from '@src/plugins/dashboardSentryPlugin';
import {
  DashboardModuleFederationV2Plugin,
  DashboardModuleFederationPlugin,
} from '@src/plugins/DashboardFederationPlugin';
import { getCommonRules } from '@src/plugins/withDashboardCore/rules';
import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';
import { CONSUMER_APP_CONSTANTS, envConfig } from '@src/plugins/withDashboardCore/env';
import { getSentryMeta } from '@src/plugins/dashboardSentryPlugin/utils';
import { getAliasedDashboardModules } from '@src/plugins/withDashboardCore/utils/getAliasedDashboardModules';
import { supportedExtensionsToResolveForBrowser } from '@src/plugins/withDashboardCore/core/withDashboardWebpack/constants';

type withDashboardBrowserWebpackConfigType = (
  options: WithDashboardWebpackConfigs,
) => ReturnType<WithDashboardWebpackType>['browserConfig'];

export const withDashboardBrowserWebpackConfig: withDashboardBrowserWebpackConfigType = (args) => {
  return () => {
    const { browserBundlerOptions: options, extendBrowserWebpackConfig } = args;
    const MiniCssExtractPlugin = require(externalDeps['mini-css-extract-plugin']);
    const ReactRefreshWebpackPlugin = require(externalDeps['@pmmmwh/react-refresh-webpack-plugin']);
    const NodePolyfillPlugin = require(externalDeps['node-polyfill-webpack-plugin']);
    const TerserPlugin = require(externalDeps['terser-webpack-plugin']);
    const LoadablePlugin = require(externalDeps['@loadable/webpack-plugin']);
    const CompressionPlugin = require(externalDeps['compression-webpack-plugin']);
    const { BundleAnalyzerPlugin } = require(externalDeps['webpack-bundle-analyzer']);
    const ErrorOverlayPlugin = require(externalDeps['error-overlay-webpack-plugin']);
    const CssMinimizerPlugin = require(externalDeps['css-minimizer-webpack-plugin']);
    const WebpackBar = require(externalDeps['webpackbar']);

    const { APP_VERSION, isDev, isProd, STAGE, isCI, isModuleFederationV2, isWebpackWithSwc } =
      CONSUMER_APP_CONSTANTS;

    if (!STAGE) {
      throw new Error('[@libs/shared-core] STAGE is not defined');
    }

    // prettier-ignore
    const isSentryEnabled = ['production', 'canary'].includes(STAGE!) && Boolean(APP_VERSION && options?.sentryConfig);

    const isShell = [DASHBOARD_FEDERATED_MODULES.SHELL].includes(options.moduleName);

    if (isSentryEnabled) {
      if (!Boolean(options?.sentryConfig?.dsn))
        throw new Error('[@libs/shared-core] Sentry dsn is missing!');
      if (!Boolean(options?.sentryConfig?.project))
        throw new Error('[@libs/shared-core] Sentry project is missing!');
    }

    const sentryMeta =
      (isSentryEnabled &&
        getSentryMeta({
          env: {
            APP_VERSION,
            STAGE,
          },
          moduleName: options.moduleName,
          sentryProject: options.sentryConfig?.project!,
        })) ||
      {};

    const extraMetaForConsumerApp = {
      ...CONSUMER_APP_CONSTANTS,
      ...sentryMeta,
      externalDeps,
    };

    const consumerAppBrowserConfig =
      extendBrowserWebpackConfig?.(
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
        extraMetaForConsumerApp,
      ) || ({} as RestrictedConfig);

    const finalConfig: Partial<Configuration> = {
      mode: isDev ? 'development' : 'production',
      name: `${options.moduleName}.browser`,
      entry: consumerAppBrowserConfig.entry,
      stats: isDev ? 'minimal' : 'verbose',
      infrastructureLogging: {
        level: isDev ? 'error' : 'verbose',
      },
      /**
       * TODO: Migration to modern builds to be planned later (after evaluating user base)
       */
      target: envConfig.isLegacyMode ? 'web' : 'es2017',
      parallelism: isDev ? 100 : 1000,
      watchOptions: {
        ignored: ['**/node_modules/**', '**/src/server/**', '**/build/server/**'],
      },
      devtool:
        !isProd && isSentryEnabled
          ? 'hidden-source-map' // For Prod Env (Only used for sentry ref)
          : isDev
          ? 'eval-cheap-module-source-map' // For balanced HMR compile time
          : 'source-map', // For better debugging in devstack env
      experiments: {
        // backCompat: false,
        outputModule: !envConfig.isLegacyMode,
      },
      optimization: {
        minimize: !isDev,
        moduleIds: 'deterministic',
        minimizer: isDev
          ? []
          : [
              new TerserPlugin({
                parallel: true,
              }),
              new CssMinimizerPlugin({ parallel: true }),
            ],
        // providedExports: true,
        // usedExports: true,
        // removeAvailableModules: true,
        // removeEmptyChunks: true,
        sideEffects: !isDev,
        runtimeChunk: false,
        splitChunks:
          isDev || !isModuleFederationV2
            ? false
            : {
                chunks: 'all',
              },
      },
      cache: isDev
        ? {
            type: 'filesystem',
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
        pathinfo: false,
        path: path.resolve(process.cwd(), './build/browser'),
        // asyncChunks: true,
        hashFunction: !isDev ? 'xxhash64' : undefined,
        uniqueName: options.moduleName,
        module: !envConfig.isLegacyMode,
        clean: !isDev,
        publicPath: process.env.UNIVERSE_PUBLIC_ASSETS_URL,
        filename: !isDev
          ? `js/[name].${options.moduleName}.[chunkhash:8].js`
          : `js/[name].${options.moduleName}.js`,
        chunkFilename: !isDev
          ? `js/[name].${options.moduleName}.[chunkhash:8].js`
          : `js/[name].${options.moduleName}.js`,
        // iife: true,
        // ignoreBrowserWarnings: true,
      },
      // @ts-ignore
      devServer: isDev
        ? {
            allowedHosts: 'all',
            server: 'https',
            devMiddleware: {
              publicPath: process.env.UNIVERSE_PUBLIC_ASSETS_URL,
              // writeToDisk: true,
            },
            historyApiFallback: {
              rewrites: [
                {
                  from: /^\/offline.html/,
                  to: `${process.env.UNIVERSE_PUBLIC_ASSETS_URL}/offline.html`,
                },
                {
                  from: /./,
                  to: process.env.UNIVERSE_PUBLIC_ASSETS_URL,
                },
              ],
            },
            port: process.env.PORT,
            client: {
              overlay: false,
              webSocketURL: {
                pathname: '/sockjs-node',
                port: process.env.PORT,
                hostname: 'localhost',
              },
              webSocketTransport: 'sockjs',
            },
            webSocketServer: {
              type: 'sockjs',
              options: {
                path: '/sockjs-node',
              },
            },
            compress: true,
            hot: true,
            static: {
              serveIndex: false,
            },
            headers: {
              'Access-Control-Allow-Origin': '*',
            },
          }
        : undefined,
      resolve: {
        modules: ['node_modules'],
        extensions: supportedExtensionsToResolveForBrowser,
        alias: {
          ...consumerAppBrowserConfig.resolve?.alias,
          ...getAliasedDashboardModules({
            moduleName: options.moduleName,
          }),
        },
      },
      plugins: [
        isDev && new ErrorOverlayPlugin(),
        !isCI &&
          new WebpackBar({
            color: 'blue',
            name: `[@libs/shared-core] ${options.moduleName}`,
            profile: false,
          }),
        isDev &&
          new ReactRefreshWebpackPlugin({
            overlay: false,
          }),
        !isDev &&
          new CompressionPlugin({
            filename: '[path][base].gz[query]',
            algorithm: 'gzip',
            test: /\.(m?js|css)$/,
          }),
        isProd && !isWebpackWithSwc &&
          new BundleAnalyzerPlugin({
            analyzerMode: 'static',
            openAnalyzer: false,
            reportFilename: `${options.moduleName}.bundle-analysis.html`,
          }),
        isShell &&
          new LoadablePlugin({
            filename: `${options.moduleName}.loadable-stats.json`,
            writeToDisk: true,
          }),
        new DefinePlugin({
          __STAGE__: JSON.stringify(process.env.STAGE),
          __APP_VERSION__: JSON.stringify(APP_VERSION),
          __APP_NAME__: JSON.stringify(options.moduleName),
          __BUILD_MODE__: JSON.stringify(envConfig.buildMode),
          __IS_WEBPACK_WITH_SWC__: JSON.stringify(options.isWebpackWithSwc ?? isWebpackWithSwc),
        }),
        new IgnorePlugin({
          resourceRegExp: /^\.\/locale$/,
          contextRegExp: /moment$/,
        }),
        new NodePolyfillPlugin(),
        new ProvidePlugin({
          moment: 'moment',
        }),
        !isDev &&
          new MiniCssExtractPlugin({
            filename: 'css/[name].[contenthash].css',
            chunkFilename: 'css/[id].[contenthash].css',
            attributes: { 'data-project': options.moduleName },
          }),
        isSentryEnabled &&
          dashboardSentryPlugin({
            moduleName: options.moduleName,
            sentryDSN: options.sentryConfig!.dsn,
            sentryProject: options.sentryConfig!.project,
          }),
        Boolean(options.moduleFederationConfig) &&
          (isModuleFederationV2
            ? new DashboardModuleFederationV2Plugin({
                name: options.moduleName,
                exposedDir: options.moduleFederationConfig!.exposedDir,
                remotes: options.moduleFederationConfig!.remotes,
                isDev,
              })
            : new DashboardModuleFederationPlugin({
                name: options.moduleName,
                exposedDir: options.moduleFederationConfig!.exposedDir,
                remotes: options.moduleFederationConfig!.remotes,
              })),
        ...(consumerAppBrowserConfig.plugins || []),
      ].filter(Boolean),
      module: {
        rules: [
          ...getCommonRules({
            isDev,
            isInAppDir: false,
            isNodeApp: false,
            isShell,
            isWebpackWithSwc: options.isWebpackWithSwc ?? isWebpackWithSwc,
            moduleName: options.moduleName,
          }),
          ...(consumerAppBrowserConfig.module?.rules || []), // Add rules if any (by consumer app)
        ],
      },
    };

    console.log(`[@libs/shared-core]`, finalConfig);

    return finalConfig;
  };
};
