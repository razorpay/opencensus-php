const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');
const { DefinePlugin } = require('webpack');
const path = require('path');

const SHELL_SENTRY_DSN = `${process.env.SHELL_SENTRY_DSN}`;
const SHELL_SENTRY_PROJECT = `${process.env.SHELL_SENTRY_PROJECT}`;

const SHELL_REMOTES = [
  DASHBOARD_FEDERATED_MODULES.SHELL,
  DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD,
  DASHBOARD_FEDERATED_MODULES.ONE_HOME,
  DASHBOARD_FEDERATED_MODULES.PAYROLL_APP,
];

const SHELL_SENTRY_CONFIG = {
  dsn: SHELL_SENTRY_DSN,
  project: SHELL_SENTRY_PROJECT,
};

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.SHELL,
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/client/exposed'),
      remotes: SHELL_REMOTES,
    },
    sentryConfig: SHELL_SENTRY_CONFIG,
  },
  serverBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.SHELL_SERVER,
    nodeFederationConfig: {
      remotes: SHELL_REMOTES,
    },
    streamFederationConfig: {
      remotes: SHELL_REMOTES,
    },
    sentryConfig: SHELL_SENTRY_CONFIG,
  },
  extendBrowserWebpackConfig: (config, { sentryAppVersion }) => {
    config.entry.push('./src/client');

    config.plugins.push(
      new DefinePlugin({
        __SHELL_CLIENT_SENTRY_VERSION__: JSON.stringify(sentryAppVersion),
        __SHELL_SENTRY_DSN__: JSON.stringify(SHELL_SENTRY_DSN),
      }),
    );

    return config;
  },
  extendServerWebpackConfig: (config, { sentryAppVersion }) => {
    console.log(sentryAppVersion);

    config.entry.push('./src/server');

    config.plugins.push(
      new DefinePlugin({
        __SHELL_SERVER_SENTRY_VERSION__: JSON.stringify(sentryAppVersion),
        __SHELL_SENTRY_DSN__: JSON.stringify(SHELL_SENTRY_DSN),
      }),
    );

    return config;
  },
});
