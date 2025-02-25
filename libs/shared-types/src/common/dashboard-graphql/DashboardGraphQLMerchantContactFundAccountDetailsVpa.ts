import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantContactFundAccountDetailsVpa = {
  __typename?: 'MerchantContactFundAccountDetailsVPA';
  address: DashboardGraphQLScalars['VPA'];
  handle?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};