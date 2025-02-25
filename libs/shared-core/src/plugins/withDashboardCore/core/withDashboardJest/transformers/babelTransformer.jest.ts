import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';
import { dashboardBabelConfig } from '../../../transpilers/babel/babel.transpiler';
import { DASHBOARD_FEDERATED_MODULES } from '@src/constants';

const { JEST_BROWSER_MODULE_NAME, JEST_SERVER_MODULE_NAME } = process.env;

const isShellServer = [DASHBOARD_FEDERATED_MODULES.SHELL_SERVER].includes(
  JEST_SERVER_MODULE_NAME as DASHBOARD_FEDERATED_MODULES,
);

const babelOptions = dashboardBabelConfig({
  isDev: false,
  isNodeApp: isShellServer,
  isShell:
    isShellServer ||
    [DASHBOARD_FEDERATED_MODULES.SHELL].includes(
      JEST_BROWSER_MODULE_NAME as DASHBOARD_FEDERATED_MODULES,
    ),
  moduleName: (JEST_BROWSER_MODULE_NAME || JEST_SERVER_MODULE_NAME) as string,
});

export default require(externalDeps['babel-jest']).createTransformer(babelOptions);
