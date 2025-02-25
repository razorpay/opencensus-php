import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPayoutLink } from './index';
export type DashboardGraphQLPayoutLinksResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutLinksResponse';
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  payoutLinks: Array<DashboardGraphQLPayoutLink>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};