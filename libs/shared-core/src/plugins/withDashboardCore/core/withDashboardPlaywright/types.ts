import { defineConfig } from '@playwright/test';
import { DASHBOARD_FEDERATED_MODULES } from '@src/constants';

export type PlaywrightConfigType = Parameters<typeof defineConfig>[0];

export type PlaywrightBaseOptionsType = {
  playwrightOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES;
  };
};

export type ExtendPlaywrightConfigArgs = (
  config: Pick<Partial<PlaywrightConfigType>, "testDir">,
) => Partial<PlaywrightConfigType>;

export type WithDashboardPlaywrightConfigs = {
  extendPlaywrightConfig?: ExtendPlaywrightConfigArgs;
} & PlaywrightBaseOptionsType;

export type WithDashboardJestType = (
  consumerConfigs: WithDashboardPlaywrightConfigs,
) => Partial<PlaywrightConfigType> | void;
