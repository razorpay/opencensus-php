import { DASHBOARD_FEDERATED_MODULES } from "../../../constants";
import { ModuleFederationShared } from "./SharedDependencyOptions";

/**
 * Options used for configuring the ModuleFederationPlugin.
 */
export interface ModuleFederationOptions {
  name: DASHBOARD_FEDERATED_MODULES;
  exposedDir?: string;
  remotes?: DASHBOARD_FEDERATED_MODULES[];
  isDev?: boolean;
}
