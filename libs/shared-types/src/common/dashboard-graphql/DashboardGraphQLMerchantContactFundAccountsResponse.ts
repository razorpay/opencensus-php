import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLMerchantContactFundAccount, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantContactFundAccountsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantContactFundAccountsResponse';
  fundAccounts?: DashboardGraphQLMaybe<Array<DashboardGraphQLMerchantContactFundAccount>>;
  hasMore: DashboardGraphQLScalars['Boolean'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};