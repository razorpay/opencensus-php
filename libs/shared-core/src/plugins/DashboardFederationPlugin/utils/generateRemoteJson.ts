import { DASHBOARD_ENVIRONMENTS, DASHBOARD_FEDERATED_MODULES } from '../../../constants';
import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '../../../configs';
import { isTargetRemotePointingToLocal } from './isRemotePointingDevServer';

const PROJECT_ASSET_BASEURL_MAP = {
  [DASHBOARD_ENVIRONMENTS.DEVELOPMENT]:
    'https://localhost:__APP_PORT__/dashboard/core-bundles/__APP_NAME__/',
  [DASHBOARD_ENVIRONMENTS.DEVSTACK]:
    'https://dashboard.dev.razorpay.in/dashboard/core-bundles/__APP_NAME__/',
  [DASHBOARD_ENVIRONMENTS.CANARY]:
    'https://dashboard-assets.razorpay.com/dashboard-canary/core-bundles/__APP_NAME__/',
  [DASHBOARD_ENVIRONMENTS.PRODUCTION]:
    'https://dashboard-assets.razorpay.com/dashboard/core-bundles/__APP_NAME__/',
};

const getRemoteBridge = ({
  nxProjectName,
  moduleEnvironment,
  modulePort,
  fileName,
}: {
  nxProjectName: string;
  moduleEnvironment: DASHBOARD_ENVIRONMENTS;
  modulePort: number;
  fileName: string;
}) => {
  return `${PROJECT_ASSET_BASEURL_MAP[moduleEnvironment]
    .replace('__APP_PORT__', `${modulePort}`)
    .replace('__APP_NAME__', nxProjectName)}${fileName}`;
};

/**
 * Generates the remotes configuration based on the environment (dev or prod).
 * @param remotes - The array of remotes to configure.
 * @returns - An object mapping remotes to their appropriate URLs.
 */
export const generateRemotesJson = (remotes: DASHBOARD_FEDERATED_MODULES[]) => {
  return remotes.reduce<Record<string, string>>((prevRemotes, currRemote) => {
    const {
      consumerRemoteImportName,
      fileName,
      nxProjectName,
      devServerPort: modulePort,
      moduleName,
    } = DASHBOARD_FEDERATED_MODULE_CONFIGS[currRemote];

    const getRemoteValue = () => {
      switch (process.env.STAGE as DASHBOARD_ENVIRONMENTS) {
        case 'development': {
          if (isTargetRemotePointingToLocal(currRemote)) {
            // Handle dev mode
            return getRemoteBridge({
              fileName,
              moduleEnvironment: DASHBOARD_ENVIRONMENTS.DEVELOPMENT,
              modulePort,
              nxProjectName,
            });
          } else {
            // Fall back as devstack
            return getRemoteBridge({
              fileName,
              moduleEnvironment: DASHBOARD_ENVIRONMENTS.DEVSTACK,
              modulePort,
              nxProjectName,
            });
          }
        }
        case 'devstack':
          return getRemoteBridge({
            fileName,
            moduleEnvironment: DASHBOARD_ENVIRONMENTS.DEVSTACK,
            modulePort,
            nxProjectName,
          });
        case 'canary':
          // Will always rely on runtime injected value for browser, for server will be taken from
          return getRemoteBridge({
            fileName,
            moduleEnvironment: DASHBOARD_ENVIRONMENTS.CANARY,
            modulePort,
            nxProjectName,
          });
        case 'production':
          // Will always rely on runtime injected value for browser, for server will be taken from
          return getRemoteBridge({
            fileName,
            moduleEnvironment: DASHBOARD_ENVIRONMENTS.PRODUCTION,
            modulePort,
            nxProjectName,
          });
        default:
          return null;
      }
    };

    const remoteVal = getRemoteValue();

    const remotes = prevRemotes;

    if (remoteVal) {
      remotes[consumerRemoteImportName] = `${moduleName}@${remoteVal}`;
    } else {
      throw new Error('MF is not supported in this environment!');
    }
    return remotes;
  }, {});
};
