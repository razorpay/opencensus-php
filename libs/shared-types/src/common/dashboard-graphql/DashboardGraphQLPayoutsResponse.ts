import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPayout } from './index';
export type DashboardGraphQLPayoutsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutsResponse';
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  payouts: Array<DashboardGraphQLPayout>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};