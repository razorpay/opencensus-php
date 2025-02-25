import { DASHBOARD_FEDERATED_MODULES } from '@src/constants';
import type { Config as JestConfig } from 'jest';

export type JestBaseOptionsType = {
  browserJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES;
  };
  serverJestOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES;
  };
};

export type BrowserJestConfigType = (config: Partial<JestConfig>) => Partial<JestConfig>;

export type ServerJestConfigType = (config: Partial<JestConfig>) => Partial<JestConfig>;

export type WithDashboardJestConfigs = {
  extendBrowserJestConfig?: BrowserJestConfigType;
  extendServerJestConfig?: ServerJestConfigType;
} & JestBaseOptionsType;

export type WithDashboardJestType = (
  consumerConfigs: WithDashboardJestConfigs,
) => Partial<JestConfig> | void;
