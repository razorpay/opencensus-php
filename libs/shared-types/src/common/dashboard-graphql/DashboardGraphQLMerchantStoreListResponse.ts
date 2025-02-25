import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantStore } from './index';
export type DashboardGraphQLMerchantStoreListResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantStoreListResponse';
  count: DashboardGraphQLScalars['NonNegativeInt'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  page: DashboardGraphQLScalars['NonNegativeInt'];
  stores: Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantStore>>;
  total: DashboardGraphQLScalars['NonNegativeInt'];
  totalPages: DashboardGraphQLScalars['NonNegativeInt'];
};