import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLSettlement } from './index';
export type DashboardGraphQLSettlementsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLSettlementsResponse';
  hasMore: DashboardGraphQLScalars['Boolean'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  settlements: Array<DashboardGraphQLSettlement>;
  total: DashboardGraphQLScalars['NonNegativeInt'];
};