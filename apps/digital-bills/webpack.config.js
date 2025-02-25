const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');

const DIGITAL_BILLS_SENTRY_DSN = `${process.env.DIGITAL_BILLS_SENTRY_PROJECT}`;
const DIGITAL_BILLS_SENTRY_PROJECT = `${process.env.DIGITAL_BILLS_SENTRY_DSN}`;

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.DIGITAL_BILLS,
    sentryConfig: {
      dsn: DIGITAL_BILLS_SENTRY_DSN,
      project: DIGITAL_BILLS_SENTRY_PROJECT,
    },
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/exposed'),
      remotes: [DASHBOARD_FEDERATED_MODULES.SHELL],
    },
  },
  extendBrowserWebpackConfig: (config) => {
    config.entry.push('./src/bootstrap/bootstrap');

    return config;
  },
});
