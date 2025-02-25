import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPaymentAnalyticsFilterByDeviceEnum, DashboardGraphQLPaymentAnalyticsFilterByOsEnum, DashboardGraphQLPaymentAnalyticsFilterBySdkEnum } from './index';
export type DashboardGraphQLPaymentAnalytics = {
  __typename?: 'DashboardGraphQLPaymentAnalytics';
  bank?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  device?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAnalyticsFilterByDeviceEnum>;
  issuer?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  method?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  network?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  os?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAnalyticsFilterByOsEnum>;
  platform?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAnalyticsFilterBySdkEnum>;
  type?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  value: DashboardGraphQLScalars['BigInt'];
  wallet?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};