import { Configuration } from 'webpack';
import { ModuleFederationOptions } from '@src/plugins/DashboardFederationPlugin/types/DashboardModuleFederationOptions';
import { DASHBOARD_FEDERATED_MODULES } from '@src/constants';

export type WebpackBaseOptionsType = {
  moduleName: DASHBOARD_FEDERATED_MODULES;
  sentryConfig?: {
    dsn: string;
    project: string;
  };
};

export type BrowserWebpackOptionsType = {
  browserBundlerOptions: {
    moduleFederationConfig?: Pick<ModuleFederationOptions, 'exposedDir' | 'remotes'>;
  } & WebpackBaseOptionsType;
};

export type ServerWebpackOptionsType = {
  serverBundlerOptions:  {
    nodeFederationConfig?: Pick<ModuleFederationOptions, 'exposedDir' | 'remotes'>;
    streamFederationConfig?: Pick<ModuleFederationOptions, 'exposedDir' | 'remotes'>;
  } & WebpackBaseOptionsType;
};

export type RestrictedConfig = Pick<Configuration, 'entry' | 'plugins'> & {
  module: {
    rules?: any[];
  };
  resolve: {
    alias?: Record<string, string>;
  };
};

export type BrowserWebpackConfigType = (
  config: RestrictedConfig,
  additionalMeta: any,
) => Partial<Configuration>;

export type ServerWebpackConfigType = (
  config: RestrictedConfig,
) => Partial<Configuration>;

export type WithDashboardWebpackConfigs = {
  extendBrowserWebpackConfig?: BrowserWebpackConfigType;
  extendServerWebpackConfig?: ServerWebpackConfigType;
} & BrowserWebpackOptionsType &
  ServerWebpackOptionsType;

export type WithDashboardWebpackType = (consumerConfigs: WithDashboardWebpackConfigs) => {
  browserConfig?: () => ReturnType<BrowserWebpackConfigType>;
  serverConfig?: () => ReturnType<ServerWebpackConfigType>;
};
