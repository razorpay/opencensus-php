const path = require('path');
const { DefinePlugin } = require('webpack');
const APP_VERSION = process.env.APP_VERSION;
const ModuleFederationPlugin = require('webpack/lib/container/ModuleFederationPlugin');
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const deps = require('./package.json').dependencies;

module.exports = {
  browserConfig: ({ config }) => {
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

    config.module.rules.push({
      test: /\.(graphql|gql)$/,
      exclude: /node_modules/,
      loader: 'graphql-tag/loader',
    });

    // This is required for importing files from @dashboard/shared-utils
    // @see https://stackoverflow.com/a/70941647/7435656

    // TODO: Need to make sure the correct js/jsx/tsx loader first
    config.module.rules[0].exclude = new RegExp(
      '/node_modules/(?!' +
        '(?:.pnpm/)?' + // Match the .pnpm/ prefix if it exists
        '(' +
        'react-intl|' +
        'intl-messageformat|' +
        '@formatjs/icu-messageformat-parser|' +
        '@commander|' +
        '@razorpay|' +
        '@universe|' +
        '@sentry' +
        ')/' +
        ').*/',
    );

    config.module.rules[0].resolve = { fullySpecified: false };

    // Universe adds path to the filename which won't work when importing assets from a parent directory e.g. shared-ui.
    // @see https://github.com/razorpay/frontend-universe/blob/master/packages/universe-pack/src/configs/webpackCommon.js#L24

    config.module.rules[1].generator.filename = 'static/[hash][name][ext]';
    config.plugins.push(
      new NodePolyfillPlugin(),
      new ModuleFederationPlugin({
        name: 'pos',
        filename: `pos.remoteEntry.js`,
        exposes: {
          './PosApp': './src/bootstrap/Wrapper',
        },

        shared: [
          {
            ...Object.keys(deps).reduce((dependencies, key) => {
              dependencies[key] = deps[key];
              return dependencies;
            }, {}),

            react: {
              requiredVersion: deps.react,
              singleton: true,
            },

            'react-dom': {
              requiredVersion: deps['react-dom'],
              singleton: true,
            },

            'react-router-dom': {
              requiredVersion: deps['react-router-dom'],
              singleton: true,
            },

            'react-query': {
              requiredVersion: deps['react-query'],
              singleton: true,
            },

            'styled-components': {
              requiredVersion: deps['styled-components'],
              singleton: true,
            },

            zustand: {
              requiredVersion: deps.zustand,
              singleton: true,
            },

            '@razorpay/blade': {
              requiredVersion: deps['@razorpay/blade'],
              singleton: true,
            },
          },
        ],

        remotes: {
          shell: `shell@${process.env.UNIVERSE_PUBLIC_SHELL_REMOTE_ENTRY_PATH}/dist/merchant-shell.remoteEntry.js`,
        },
      }),
    );

    config.optimization.runtimeChunk = false;

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
      'apps/pos': path.resolve(__dirname),
    };

    config.plugins.push(
      new CopyWebpackPlugin({
        patterns: [
          {
            from: './src/manifests/web-manifest.json',
            to: `./manifest.json`,
            transform(content) {
              const parsedContent = JSON.parse(content);
              const baseURL = process.env.SHELL_BASE_URL || 'https://dashboard.dev.razorpay.in';
              parsedContent.start_url = `${baseURL}/app/pos-sales`;
              parsedContent.scope = `${baseURL}/app`;
              return JSON.stringify(parsedContent);
            },
          },
          {
            from: './src/manifests/icons/*',
            to: path.resolve(__dirname, 'build', 'browser', 'icons', '[name].[ext]'),
          },
        ],
      }),
    );
    return config;
  },
};
