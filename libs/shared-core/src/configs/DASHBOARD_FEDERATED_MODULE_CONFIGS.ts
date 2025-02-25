import { generateBaseMfeBaseMeta } from '../utils/generateBaseMfeBaseMeta';
import {
  DASHBOARD_APP_BUNDLE_TYPE,
  DASHBOARD_FEDERATED_MODULES,
  DASHBOARD_FEDERATED_MODULE_TYPE,
} from '../constants';

type DASHBOARD_FEDERATED_MODULE_CONFIG_TYPE = {
  appDirFromRoot?: string;
  devStartCommand?: string;
  consumerRemoteImportName: string;
  moduleName: DASHBOARD_FEDERATED_MODULES;
  fileName: string;
  devServerPort: number;
  buildType?: DASHBOARD_APP_BUNDLE_TYPE;
  nxProjectName: string;
};

// @ts-ignore
export const DASHBOARD_FEDERATED_MODULE_CONFIGS: Record<
  DASHBOARD_FEDERATED_MODULES,
  DASHBOARD_FEDERATED_MODULE_CONFIG_TYPE
> = {
  [DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD]: {
    appDirFromRoot: 'web/js/merchant',
    devStartCommand: `PROJECT=payments-dashboard pnpm nx start`,
    devServerPort: 8080,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.DASHBOARD,
      moduleName: DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.LA_DASHBOARD]: {
    appDirFromRoot: 'web/js/merchantLA',
    devStartCommand: `PROJECT=la-dashboard pnpm nx start`,
    devServerPort: 8081,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.DASHBOARD,
      moduleName: DASHBOARD_FEDERATED_MODULES.LA_DASHBOARD,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.NEWAUTH_DASHBOARD]: {
    appDirFromRoot: 'web/js/newAuth',
    devStartCommand: `PROJECT=newauth-dashboard pnpm nx start`,
    devServerPort: 8082,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.DASHBOARD,
      moduleName: DASHBOARD_FEDERATED_MODULES.NEWAUTH_DASHBOARD,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.POKEDEX_DASHBOARD]: {
    appDirFromRoot: 'web/js/pokedex',
    devStartCommand: `PROJECT=pokedex-dashboard pnpm nx start`,
    devServerPort: 8083,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.DASHBOARD,
      moduleName: DASHBOARD_FEDERATED_MODULES.POKEDEX_DASHBOARD,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.SHELL]: {
    appDirFromRoot: 'apps/shell',
    devStartCommand: 'pnpm nx start:browser',
    devServerPort: 8000,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
      moduleName: DASHBOARD_FEDERATED_MODULES.SHELL,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.SHELL_SERVER]: {
    appDirFromRoot: 'apps/shell',
    devStartCommand: 'pnpm nx start:server',
    devServerPort: 8888,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.SERVER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
      moduleName: DASHBOARD_FEDERATED_MODULES.SHELL_SERVER,
    }),
  },
  // DO NOT MODIFY THIS (EXCEPTION)
  // @ts-ignore
  [DASHBOARD_FEDERATED_MODULES.SHELL_SERVER_STREAM]: {
    devServerPort: 8888,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.SERVER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
      moduleName: DASHBOARD_FEDERATED_MODULES.SHELL_SERVER_STREAM,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.POS]: {
    appDirFromRoot: 'apps/pos',
    devStartCommand: 'pnpm nx start',
    devServerPort: 9000,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
      moduleName: DASHBOARD_FEDERATED_MODULES.POS,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.DIGITAL_BILLS]: {
    appDirFromRoot: 'apps/digital-bills',
    devStartCommand: 'pnpm nx start',
    devServerPort: 9090,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
      moduleName: DASHBOARD_FEDERATED_MODULES.DIGITAL_BILLS,
    }),
  },
  [DASHBOARD_FEDERATED_MODULES.SELF_SERVE]: {
    appDirFromRoot: 'apps/self-serve',
    devStartCommand: 'pnpm nx start',
    devServerPort: 9999,
    buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
    ...generateBaseMfeBaseMeta({
      moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
      moduleName: DASHBOARD_FEDERATED_MODULES.SELF_SERVE,
    }),
  },
};
