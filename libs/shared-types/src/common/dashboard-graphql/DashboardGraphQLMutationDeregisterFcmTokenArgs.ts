import { DashboardGraphQLProductTypeEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationDeregisterFcmTokenArgs = {
  productType: DashboardGraphQLProductTypeEnum;
  tokenIdentifier: DashboardGraphQLScalars['String'];
};