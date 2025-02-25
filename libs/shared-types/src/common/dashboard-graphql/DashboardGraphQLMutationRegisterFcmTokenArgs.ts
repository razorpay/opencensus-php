import { DashboardGraphQLScalars, DashboardGraphQLPlatformEnum, DashboardGraphQLProductTypeEnum } from './index';
export type DashboardGraphQLMutationRegisterFcmTokenArgs = {
  fcmToken: DashboardGraphQLScalars['String'];
  platform: DashboardGraphQLPlatformEnum;
  productType: DashboardGraphQLProductTypeEnum;
  tokenIdentifier: DashboardGraphQLScalars['String'];
};