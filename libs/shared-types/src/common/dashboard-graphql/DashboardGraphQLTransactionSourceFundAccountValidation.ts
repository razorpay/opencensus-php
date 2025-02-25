import { DashboardGraphQLMaybe, DashboardGraphQLMerchantContactFundAccount, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLTransactionSourceFundAccountValidation = {
  __typename?: 'DashboardGraphQLTransactionSourceFundAccountValidation';
  fundAccount?: DashboardGraphQLMaybe<DashboardGraphQLMerchantContactFundAccount>;
  id: DashboardGraphQLScalars['ID'];
  utr?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};