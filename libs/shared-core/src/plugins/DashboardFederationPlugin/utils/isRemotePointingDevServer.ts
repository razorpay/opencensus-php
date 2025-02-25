import { DASHBOARD_FEDERATED_MODULES } from '../../../constants';
import { cacheManager } from '../../../cache';

export const isTargetRemotePointingToLocal = (moduleName: DASHBOARD_FEDERATED_MODULES) => {
  return (
    Boolean(cacheManager.readData()?.selectedRemotes) &&
    Array.isArray(cacheManager.readData()?.selectedRemotes) &&
    (cacheManager.readData()!.selectedRemotes as DASHBOARD_FEDERATED_MODULES[]).includes(moduleName)
  );
};
