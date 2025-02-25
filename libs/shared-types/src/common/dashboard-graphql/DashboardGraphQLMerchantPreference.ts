import { DashboardGraphQLScalars, DashboardGraphQLMerchantPreferenceProductTypeEnum } from './index';
export type DashboardGraphQLMerchantPreference = {
  __typename?: 'DashboardGraphQLMerchantPreference';
  createdAt: DashboardGraphQLScalars['DateTime'];
  group: DashboardGraphQLScalars['String'];
  id: DashboardGraphQLScalars['ID'];
  merchantId: DashboardGraphQLScalars['ID'];
  productType: DashboardGraphQLMerchantPreferenceProductTypeEnum;
  type: DashboardGraphQLScalars['String'];
  updatedAt: DashboardGraphQLScalars['DateTime'];
  value: DashboardGraphQLScalars['String'];
};