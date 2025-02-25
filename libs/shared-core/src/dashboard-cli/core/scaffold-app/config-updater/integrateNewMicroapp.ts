import { updateDashboardFederatedModuleConfigs } from './tools/updateDashboardFederatedModuleConfigs';
import { updateDashboardFederatedModulesEnum } from './tools/updateDashboardFederatedModulesEnum';
import { updateTsConfigPaths } from './tools/updateTsConfigPaths';
import { updateWorkspacePackages } from './tools/updateWorkspaceYaml';

export const integrateNewMicroapp = async ({
  appName,
  appPort,
}: {
  appName: string;
  appPort: number;
}) => {
  await updateDashboardFederatedModulesEnum({ appName });
  await updateDashboardFederatedModuleConfigs({ appName, appPort });
  await updateWorkspacePackages({ appName });
  await updateTsConfigPaths({ appName });
};
