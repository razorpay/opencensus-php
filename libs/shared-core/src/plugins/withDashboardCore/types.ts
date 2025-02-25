import { WithDashboardWebpackConfigs } from './core/withDashboardWebpack/types';
import { WithDashboardJestConfigs } from './core/withDashboardJest/types';
import { WithDashboardPlaywrightConfigs } from './core/withDashboardPlaywright/types';


export type WithDashboardArgsType = WithDashboardWebpackConfigs & WithDashboardJestConfigs & WithDashboardPlaywrightConfigs;

export type WithDashboardCoreType = (x: WithDashboardArgsType) => any;
