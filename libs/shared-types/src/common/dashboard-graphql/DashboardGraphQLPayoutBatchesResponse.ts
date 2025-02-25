import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPayoutBatch } from './index';
export type DashboardGraphQLPayoutBatchesResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutBatchesResponse';
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  payoutBatches: Array<DashboardGraphQLPayoutBatch>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};