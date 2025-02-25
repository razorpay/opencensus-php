import { CROSS_REPO_ONBOARDED_MICROAPPS } from '../config';

export interface ImportRemoteOptions {
  namespace: ModuleNamespace;
  module: string;
}

export type WebpackRequire = {
  l: (
    url: string | undefined,
    cb: (event: any) => void,
    id: string | number,
  ) => Record<string, unknown>;
};

export type WebpackRemoteContainer = {
  __initialized?: boolean;
  get(modulePath: string): () => any;
  // @ts-ignore
  init: (obj?: typeof __webpack_share_scopes__) => void;
};

export type WebpackShareScopes = Record<
  string,
  Record<string, { loaded?: 1; get: () => Promise<unknown>; from: string; eager: boolean }>
> & {
  default?: string;
};

export type ModuleNamespace = keyof typeof CROSS_REPO_ONBOARDED_MICROAPPS;
