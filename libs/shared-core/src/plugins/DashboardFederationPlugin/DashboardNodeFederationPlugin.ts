import { generateOptions, validateOptions } from './utils';
import type { ModuleFederationOptions } from './types/DashboardModuleFederationOptions';
import { NodeFederationPlugin } from '@module-federation/node';

/**
 * Custom Node Federation Plugin that dynamically generates the `exposes` object based on the provided options.
 */
export class DashboardNodeFederationPlugin extends NodeFederationPlugin {
  /**
   * Creates an instance of the custom NodeFederationPlugin.
   *
   * @param options - Configuration options for the plugin.
   * @param options.name - The unique name of this application.
   * @param options.exposedDir - The path to the directory from which the modules should be exposed.
   * @param options.remotes - Specifies remote applications that this app depends on.
   */
  constructor(options: ModuleFederationOptions) {
    if (!validateOptions(options)) {
      throw new Error(
        `Invalid remotes passed, please use "DASHBOARD_FEDERATED_MODULES" from @libs/shared-core`,
      );
    }

    const finalOptions = generateOptions(options);
    const parsedOptions = {
      ...finalOptions,
      library: {
        name: finalOptions.name,
        type: 'commonjs-module',
      },
    };
    super(parsedOptions as any);
  }

  /**
   * Applies the plugin to the Webpack compiler.
   * @param compiler - The Webpack compiler instance.
   */
  apply(compiler: any): void {
    super.apply(compiler);
  }
}

