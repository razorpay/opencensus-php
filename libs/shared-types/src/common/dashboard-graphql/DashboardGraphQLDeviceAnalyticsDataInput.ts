import { DashboardGraphQLInputMaybe, DashboardGraphQLUserAcquisitionSourceEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLDeviceAnalyticsDataInput = {
  /** The signup source from mobile can be android or ios */
  acquisitionSource?: DashboardGraphQLInputMaybe<DashboardGraphQLUserAcquisitionSourceEnum>;
  appsFlyerId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};