import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLTransaction } from './index';
export type DashboardGraphQLTransactionsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLTransactionsResponse';
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  transactions: Array<DashboardGraphQLTransaction>;
};