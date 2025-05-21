const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');

const PAYROLL_APP_SENTRY_DSN = `${process.env.PAYROLL_APP_SENTRY_DSN}`;
const PAYROLL_APP_SENTRY_PROJECT = `${process.env.PAYROLL_APP_SENTRY_PROJECT}`;

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.PAYROLL_APP,
    sentryConfig: {
      dsn: PAYROLL_APP_SENTRY_DSN,
      project: PAYROLL_APP_SENTRY_PROJECT,
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
