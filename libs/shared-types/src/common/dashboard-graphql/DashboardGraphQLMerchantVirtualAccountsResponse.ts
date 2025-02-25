import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantVirtualAccount } from './index';
export type DashboardGraphQLMerchantVirtualAccountsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantVirtualAccountsResponse';
  hasMore: DashboardGraphQLScalars['Boolean'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  virtualAccounts: Array<DashboardGraphQLMerchantVirtualAccount>;
};