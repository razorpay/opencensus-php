const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');

const ONE_HOME_SENTRY_PROJECT = `${process.env.ONE_HOME_SENTRY_PROJECT}`;
const ONE_HOME_SENTRY_DSN = `${process.env.ONE_HOME_SENTRY_DSN}`;

/**
 * `withDashboardCore` handles most of the configs maintained at platform level, refer:
 * libs/shared-core/src/plugins/withDashboardCore/configs/browserConfig.ts
 */
module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.ONE_HOME,
    sentryConfig: {
      dsn: ONE_HOME_SENTRY_PROJECT,
      project: ONE_HOME_SENTRY_DSN,
    },
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/exposed'),
      remotes: [DASHBOARD_FEDERATED_MODULES.SHELL],
    },
  },
  extendBrowserWebpackConfig: (config) => {
    config.entry.push('./src/bootstrap');

    return config;
  },
});
