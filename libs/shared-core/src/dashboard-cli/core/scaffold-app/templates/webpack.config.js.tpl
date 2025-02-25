const path = require('path');
const { DASHBOARD_FEDERATED_MODULES, withDashboardCore } = require('@libs/shared-core');

{{ webpackSentryConstants }}

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.{{ integratedAppName }},
    {{ webpackSentryConfig }}
    moduleFederationConfig: {
      exposedDir: path.resolve('./src/exposed'),
      remotes: {{ integratedAppRemotes }},
    },
  },
  extendBrowserWebpackConfig: (config) => {
    config.entry.push('./src/bootstrap/bootstrap');
    return config;
  },
});
