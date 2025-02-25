import { WithDashboardCoreType } from './types';
import { ConfigFileType, getConfigFileType } from './utils/getConfigFileType';
import {withDashboardEslint, withDashboardJest, withDashboardRsPack, withDashboardWebpack, withDashboardPlaywright} from "./core";

export const withDashboardCore: WithDashboardCoreType = (args) => {
  const selectedExecutionCore = getConfigFileType();
  
  console.log(`[@libs/shared-core] Selected execution core: ${selectedExecutionCore}`);

  switch (selectedExecutionCore) {
    case ConfigFileType.WEBPACK: {
      return withDashboardWebpack(args);
    };
    case ConfigFileType.JEST: {
      return withDashboardJest(args);
    };
    case ConfigFileType.ESLINT: {
      return withDashboardEslint(args);
    };
    case ConfigFileType.RSPACK: {
      return withDashboardRsPack(args);
    };
    case ConfigFileType.PLAYWRIGHT: {
      return withDashboardPlaywright(args);
    };
    default: {
      throw new Error('[@libs/shared-core] No configuration found!');
    };
  };
};


