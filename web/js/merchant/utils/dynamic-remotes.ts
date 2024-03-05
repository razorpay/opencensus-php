// @ts-nocheck

type WebpackRequire = {
  l: (
    url: string | undefined,
    cb: (event: any) => void,
    id: string | number,
  ) => Record<string, unknown>;
};

type WebpackRemoteContainer = {
  __initialized?: boolean;
  get(modulePath: string): () => any;
  init: (obj?: typeof __webpack_share_scopes__) => void;
};

type WebpackShareScopes = Record<
  string,
  Record<string, { loaded?: 1; get: () => Promise<unknown>; from: string; eager: boolean }>
> & {
  default?: string;
};

type RemoteUrl = string | (() => Promise<string>);

type ModuleScope = 'shell' | 'selfserve';

interface ImportRemoteOptions {
  url: RemoteUrl;
  scope: ModuleScope;
  module: string;
  remoteEntryFileName?: string;
}

const loadRemote = (
  url: ImportRemoteOptions['url'],
  scope: ImportRemoteOptions['scope'],
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
      scope,
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

/*
    Dynamically import a remote module using Webpack's loading mechanism:
    https://webpack.js.org/concepts/module-federation/
  */
export const importRemote = async <T>({
  url,
  scope,
  module,
  remoteEntryFileName = 'remoteEntry.js',
}: ImportRemoteOptions): Promise<T> => {
  if (process.env.UNIVERSE_PUBLIC_ENV === 'test') {
    return `${scope}/${module}`;
  }

  const remoteScope = scope as unknown as number;
  if (!window[remoteScope]) {
    let remoteUrl = '';

    if (typeof url === 'string') {
      remoteUrl = url;
    } else {
      remoteUrl = await url();
    }

    console.log('process.env.REDIRECTOR :', process.env.REDIRECTOR);

    const federatedUrl =
      process.env.REDIRECTOR || process.env.REDIRECTOR === 'true'
        ? ''
        : `/dashboard/federated-bundles/${scope}`;

    // const script = document.createElement('script');
    // let client = 'legacy';
    // if ('noModule' in script) {
    //   client = 'modern';
    // }

    const entryURL = `${remoteUrl}${federatedUrl}/build/browser/${scope}.${remoteEntryFileName}`;
    // Load the remote and initialize the share scope if it's empty
    await Promise.all([loadRemote(entryURL, scope), initSharing()]);
    if (!window[remoteScope]) {
      throw new Error(
        `Remote loaded successfully but ${scope} could not be found! Verify that the name is correct in the Webpack configuration!`,
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
