const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');

const ONBOARDING_EXPERIENCE_SENTRY_DSN = `${process.env.ONBOARDING_EXPERIENCE_SENTRY_DSN}`;
const ONBOARDING_EXPERIENCE_SENTRY_PROJECT = `${process.env.ONBOARDING_EXPERIENCE_SENTRY_PROJECT}`;

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.ONBOARDING_EXPERIENCE,
    sentryConfig: {
      dsn: ONBOARDING_EXPERIENCE_SENTRY_DSN,
      project: ONBOARDING_EXPERIENCE_SENTRY_PROJECT,
    },
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/exposed'),
      remotes: [DASHBOARD_FEDERATED_MODULES.SHELL, DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD],
    },
  },
  extendBrowserWebpackConfig: (config) => {
    config.entry.push('./src/bootstrap/bootstrap');

    config.resolve.alias = {
      'apps/onboarding-experience': path.resolve(__dirname),
      '@FTUX': path.resolve(__dirname, 'src/pages/FTUX'),
    };

    return config;
  },
});
