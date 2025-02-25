const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');

const SELF_SERVE_SENTRY_DSN = `${process.env.SELF_SERVE_SENTRY_DSN}`;
const SELF_SERVE_SENTRY_PROJECT = `${process.env.SELF_SERVE_SENTRY_PROJECT}`;

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.SELF_SERVE,
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/exposed'),
      remotes: [DASHBOARD_FEDERATED_MODULES.SHELL, DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD],
    },
    sentryConfig: {
      dsn: SELF_SERVE_SENTRY_DSN,
      project: SELF_SERVE_SENTRY_PROJECT,
    },
  },
  extendBrowserWebpackConfig: (config) => {
    config.entry.push('./src/bootstrap/bootstrap');

    config.resolve.alias = {
      'apps/self-serve': path.resolve(__dirname),
    };

    return config;
  },
});
