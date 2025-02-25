import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLSalesMerchantActivationStatusEnum } from './index';
export type DashboardGraphQLSalesOnboardedMerchant = {
  __typename?: 'DashboardGraphQLSalesOnboardedMerchant';
  createdAt: DashboardGraphQLScalars['String'];
  merchantId: DashboardGraphQLScalars['String'];
  merchantMobile: DashboardGraphQLScalars['String'];
  merchantName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  progressCompletion: DashboardGraphQLScalars['String'];
  status?: DashboardGraphQLMaybe<DashboardGraphQLSalesMerchantActivationStatusEnum>;
};