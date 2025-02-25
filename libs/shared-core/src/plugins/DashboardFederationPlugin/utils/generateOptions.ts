import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '../../../configs';
import { ModuleFederationOptions } from '../types/DashboardModuleFederationOptions';
import { generateExposesJson } from './generateExposesJson';
import { generateRemotesJson } from './generateRemoteJson';
import { generateSharedJson } from './generateSharedJson';

/**
 * Parses the base configuration for ModuleFederationPlugin.
 * @param options - The options passed to the ModuleFederationPlugin.
 * @returns - The parsed options with remotes configuration.
 */
export const generateOptions = (options: ModuleFederationOptions) => {
  const { remotes, name, exposedDir } = options;

  return {
    name,
    filename: DASHBOARD_FEDERATED_MODULE_CONFIGS[name].fileName,
    shared: generateSharedJson(),
    remotes: remotes ? generateRemotesJson(remotes) : {},
    exposes: exposedDir ? generateExposesJson(exposedDir) : {},
  };
};
