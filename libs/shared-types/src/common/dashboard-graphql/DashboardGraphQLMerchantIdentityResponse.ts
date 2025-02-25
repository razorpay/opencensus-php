import { DashboardGraphQLScalars, DashboardGraphQLMerchantIdentityTypeEnum } from './index';
export type DashboardGraphQLMerchantIdentityResponse = {
  __typename?: 'DashboardGraphQLMerchantIdentityResponse';
  businessName: DashboardGraphQLScalars['String'];
  number: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLMerchantIdentityTypeEnum;
};