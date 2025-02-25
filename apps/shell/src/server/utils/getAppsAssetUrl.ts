import { CDN_DASHBOARD_ASSETS_URL, LOCAL_DEV_REMOTES, STAGE } from '@apps/shell/src/env';


// TODO: Integrate canary namespace switch for PHP blade files too.
export const getAppsBaseAssetUrl = (
  appName: 'shell' | 'shell-server' | 'payments-dashboard' | 'la-dashboard',
) => {
  const MAIN_PATH_SUFFIX = `core-bundles/${appName}`;
  const dashboardStandardPathConvention = `${CDN_DASHBOARD_ASSETS_URL}/dashboard/${MAIN_PATH_SUFFIX}`;

  switch (STAGE) {
    case 'canary':
      return `${CDN_DASHBOARD_ASSETS_URL}/dashboard-canary/${MAIN_PATH_SUFFIX}`;
    case 'production':
    case 'devstack':
      return dashboardStandardPathConvention;
    case 'development': {
      // Here, devstack url will be used as fallback.
      switch (appName) {
        case 'la-dashboard':
          return LOCAL_DEV_REMOTES?.includes('la_dashboard')
            ? `https://localhost:8081/dashboard/${MAIN_PATH_SUFFIX}`
            : dashboardStandardPathConvention;
        case 'payments-dashboard':
          return LOCAL_DEV_REMOTES?.includes('payments_dashboard')
            ? `https://localhost:8080/dashboard/${MAIN_PATH_SUFFIX}`
            : dashboardStandardPathConvention;
        case 'shell':
          return `https://localhost:8000/dashboard/${MAIN_PATH_SUFFIX}`;
        case 'shell-server':
          return `https://localhost:8888/dashboard/${MAIN_PATH_SUFFIX}`;
        default:
          return dashboardStandardPathConvention;
      }
    }
    default:
      return dashboardStandardPathConvention;
  }
};
