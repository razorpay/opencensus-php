const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');

// JEST_TARGET via cli handles which to run
module.exports = withDashboardCore({
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.SHELL,
  },
  serverJestOptions: {
    server: {
      moduleName: DASHBOARD_FEDERATED_MODULES.SHELL_SERVER,
    },
  },
  extendBrowserJestConfig: (config) => {
    config.coverageThreshold = {
      global: {
        statements: 80,
        branches: 80,
        functions: 80,
        lines: 80,
      },
    };

    return config;
  },
  extendServerJestConfig: (config) => {
    config.coverageThreshold = {
      global: {
        statements: 80,
        branches: 80,
        functions: 80,
        lines: 80,
      },
    };

    return config;
  },
});
