import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLSalesOnboardedMerchant, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSalesOnboardedMerchants = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLSalesOnboardedMerchants';
  hasMore: DashboardGraphQLScalars['Boolean'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  merchants: Array<DashboardGraphQLSalesOnboardedMerchant>;
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};