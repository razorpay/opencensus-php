import { DASHBOARD_FEDERATED_MODULES } from '../../../constants';
import { ModuleFederationOptions } from '../types/DashboardModuleFederationOptions';

/**
 * Validates the options passed to the ModuleFederationPlugin.
 * @param options - The options to validate.
 * @returns Whether the options are valid.
 */
export const validateOptions = (options: ModuleFederationOptions): boolean => {
  const requiredKeys = ['name'];

  const hasRequiredKeys = requiredKeys.every((key) => key in options);

  const areRemotesValid = options.remotes
    ? options.remotes.every((remote) => {
        const validRemotes = Object.values(DASHBOARD_FEDERATED_MODULES);
        return validRemotes.includes(remote);
      })
    : true;

  return hasRequiredKeys && areRemotesValid;
};
