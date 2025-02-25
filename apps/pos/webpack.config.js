const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const POS_SENTRY_DSN = `${process.env.POS_SENTRY_DSN}`;
const POS_SENTRY_PROJECT = `${process.env.POS_SENTRY_PROJECT}`;

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.POS,
    sentryConfig: {
      dsn: POS_SENTRY_DSN,
      project: POS_SENTRY_PROJECT,
    },
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/exposed'),
      remotes: [DASHBOARD_FEDERATED_MODULES.SHELL, DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD],
    },
  },
  extendBrowserWebpackConfig: (config, { isProd }) => {
    config.entry.push('./src/bootstrap/bootstrap');

    config.resolve.alias = {
      'apps/pos': path.resolve(__dirname),
    };

    config.plugins.push(
      new CopyWebpackPlugin({
        patterns: [
          {
            from: isProd
              ? './src/manifests/app.prod.manifest.json'
              : './src/manifests/app.dev.manifest.json',
            to: `./manifest.json`,
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
});
