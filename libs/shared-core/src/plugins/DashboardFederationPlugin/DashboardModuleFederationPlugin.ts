import { container, type Compiler } from 'webpack';
import { generateOptions, validateOptions } from './utils';
import { type ModuleFederationOptions } from './types/DashboardModuleFederationOptions';
const { ModuleFederationPlugin } = container;

/**
 * Custom Module Federation Plugin that dynamically generates the `exposes` object based on the provided options.
 */
export class DashboardModuleFederationPlugin extends ModuleFederationPlugin {
  /**
   * Creates an instance of the custom ModuleFederationPlugin.
   *
   * @param options - Configuration options for the plugin.
   * @param options.name - The unique name of this application.
   * @param options.exposedDir - The path to the directory from which the modules should be exposed.
   * @param options.remotes - Specifies remote applications that this app depends on.
   */
  constructor(options: ModuleFederationOptions) {
    if (!validateOptions(options)) {
      throw new Error(
        `[@libs/shared-core] Invalid remotes passed, please use "DASHBOARD_FEDERATED_MODULES" from @libs/shared-core`,
      );
    }

    const finalOptions = generateOptions(options);

    super(finalOptions as any);
  }

  /**
   * Applies the plugin to the Webpack compiler.
   * @param compiler - The Webpack compiler instance.
   */
  apply(compiler: Compiler): void {
    super.apply(compiler);
  }
}
