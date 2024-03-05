const path = require('path');
const { DefinePlugin } = require('webpack');
// const SentryWebpackPlugin = require('@sentry/webpack-plugin');
const APP_VERSION = process.env.APP_VERSION;
const ModuleFederationPlugin = require('webpack/lib/container/ModuleFederationPlugin');
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin');

const deps = require('./package.json').dependencies;

module.exports = {
  browserConfig: ({ config }) => {
    // webpack browser config to run your project in browser
    // config object comes with basic webpack configurations.
    // You add/modify any property if you have justification for it :P
    config.entry.push('./src/bootstrap/bootstrap');

    config.resolve.extensions = [...config.resolve.extensions, '.ts', '.tsx'];

    if (APP_VERSION) {
      config.plugins.push(
        new DefinePlugin({
          // Use this to define the version of the app for later use in the app
          __APP_VERSION__: JSON.stringify(APP_VERSION),
        }),
      );
    }

    // config.resolve.modules = [
    // path.resolve(__dirname, 'src'),
    // path.resolve(__dirname, 'static'),
    //   './node_modules',
    //   '../node_modules',
    // ];

    // TODO: Need to make sure the correct js/jsx/tsx loader first
    config.module.rules[0].exclude =
      /node_modules\/(?!react-intl|intl-messageformat|@formatjs\/icu-messageformat-parser)/;

    // This is required for importing files from @dashboard/shared-utils
    // @see https://stackoverflow.com/a/70941647/7435656
    config.module.rules[0].resolve = { fullySpecified: false };

    // Universe adds path to the filename which won't work when importing assets from a parent directory e.g. shared-ui.
    // @see https://github.com/razorpay/frontend-universe/blob/master/packages/universe-pack/src/configs/webpackCommon.js#L24
    config.module.rules[1].generator.filename = 'static/[hash][name][ext]';

    config.plugins.push(
      new NodePolyfillPlugin(),
      new ModuleFederationPlugin({
        name: 'selfserve',
        // library: {
        //   name: 'selfserve',
        //   type: 'window'
        // },
        filename: `selfserve.remoteEntry.js`,
        exposes: {
          './SelfServeRouter': './src/bootstrap/Route/SelfServeRouter.tsx',
        },
        shared: [
          {
            ...Object.keys(deps).reduce((dependencies, key) => {
              dependencies[key] = deps[key];
              return dependencies;
            }, {}),
            xlsx: {
              singleton: true,
              version: '0.19.3',
            },
            react: {
              requiredVersion: deps.react,
              singleton: true,
              // eager: true,
            },
            'react-dom': {
              requiredVersion: deps['react-dom'],
              singleton: true,
              // eager: true,
            },
            'react-router-dom': {
              requiredVersion: deps['react-router-dom'],
              singleton: true,
              // eager: true,
            },
            'react-query': {
              requiredVersion: deps['react-query'],
              singleton: true,
              // eager: true,
            },
            'styled-components': {
              requiredVersion: deps['styled-components'],
              singleton: true,
              // eager: true,
            },
            zustand: {
              requiredVersion: deps.zustand,
              singleton: true,
              // eager: true,
            },
            '@razorpay/blade': {
              requiredVersion: deps['@razorpay/blade'],
              singleton: true,
              // eager: true,
            },
            '@razorpay/blade-old': {
              requiredVersion: deps['@razorpay/blade-old'],
              singleton: true,
              // eager: true,
            },
          },
        ],
        remotes: {
          // TODO: check if merchantLA build includes self-serve module
          shell: `shell@${process.env.UNIVERSE_PUBLIC_SHELL_REMOTE_ENTRY_PATH}/dist/merchant-shell.remoteEntry.js`,
        },
      }),
    );

    config.optimization.runtimeChunk = false;
    // config.optimization.splitChunks = false;
    config.optimization.splitChunks = {
      cacheGroups: {
        common: {
          name: 'common',
          minChunks: 4,
          enforce: true,
          priority: -15,
          chunks: 'all',
          reuseExistingChunk: true,
          test(module) {
            // this is required to make module federation work
            // with split chunks plugin
            // we exclude chunks managed by the MF plugin
            if (
              module.type === 'provide-module' ||
              module.type === 'consume-shared-module' ||
              module.type === 'remote-module'
            ) {
              return false;
            }
            return true;
          },
        },
      },
    };

    config.output.module = false;
    config.experiments.outputModule = false;

    config.resolve.alias = {
      ...config.resolve.alias,
      '@dashboard/shared-ui': path.resolve(__dirname, '../../libs/shared-ui/src'),
      '@dashboard/shared-utils': path.resolve(__dirname, '../../libs/shared-utils/src'),
      'apps/self-serve': path.resolve(__dirname),
    };

    return config;
  },
};
