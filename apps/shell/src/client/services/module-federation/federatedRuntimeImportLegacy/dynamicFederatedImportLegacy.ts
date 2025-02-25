// @ts-nocheck

import { getRuntimeRemoteUrl } from '../utils';
import {
  ImportRemoteOptions,
  ModuleNamespace,
  WebpackRemoteContainer,
  WebpackRequire,
  WebpackShareScopes,
} from './types';

const loadRemote = (
  url: string,
  namespace: ModuleNamespace,
): Promise<void> =>
  new Promise<void>((resolve, reject) => {
    const webpackRequire = __webpack_require__ as unknown as WebpackRequire;
    webpackRequire.l(
      url,
      (event) => {
        if (event?.type === 'load') {
          // Script loaded successfully:
          return resolve();
        }
        const realSrc = event?.target?.src;
        const error = new Error();
        error.message = `Loading script failed.\n(missing: ${realSrc})`;
        error.name = 'ScriptExternalLoadError';
        reject(error);
      },
      namespace,
    );
  });

const initSharing = async (): Promise<void> => {
  const webpackShareScopes = __webpack_share_scopes__ as unknown as WebpackShareScopes;
  if (!webpackShareScopes?.default) {
    await __webpack_init_sharing__('default');
  }
};

// __initialized and __initializing flags prevent some concurrent re-initialization corner cases
const initContainer = async (containerScope: unknown): Promise<void> => {
  try {
    const webpackShareScopes = __webpack_share_scopes__ as unknown as WebpackShareScopes;
    if (!containerScope.__initialized && !containerScope.__initializing) {
      containerScope.__initializing = true;
      await containerScope.init(webpackShareScopes.default);
      containerScope.__initialized = true;
      delete containerScope.__initializing;
    }
  } catch (error) {
    console.error(error);
  }
};

/**
 * @deprecated
 * Dynamically import a remote module using Webpack's loading mechanism:
 * https://webpack.js.org/concepts/module-federation/
 */
export const importRemote = async <T>({ namespace, module }: ImportRemoteOptions): Promise<T> => {
  const remoteScope = namespace as unknown as number;

  if (!window[remoteScope]) {
    const entryURL = getRuntimeRemoteUrl(namespace);

    // Load the remote and initialize the share scope if it's empty
    await Promise.all([loadRemote(entryURL, namespace), initSharing()]);
    if (!window[remoteScope]) {
      throw new Error(
        `Remote loaded successfully but ${namespace} could not be found! Verify that the name is correct in the Webpack configuration!`,
      );
    }
    // Initialize the container to get shared modules and get the module factory:
    const [, moduleFactory] = await Promise.all([
      initContainer(window[remoteScope]),
      (window[remoteScope] as unknown as WebpackRemoteContainer).get(
        module.startsWith('./') ? module : `./${module}`,
      ),
    ]);

    return moduleFactory();
  } else {
    // eslint-disable-next-line @typescript-eslint/await-thenable
    const moduleFactory = await (window[remoteScope] as unknown as WebpackRemoteContainer).get(
      module.startsWith('./') ? module : `./${module}`,
    );
    return moduleFactory();
  }
};
