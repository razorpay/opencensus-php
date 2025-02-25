import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPayout } from './index';
export type DashboardGraphQLTransactionSourceReversal = {
  __typename?: 'DashboardGraphQLTransactionSourceReversal';
  id: DashboardGraphQLScalars['ID'];
  payout?: DashboardGraphQLMaybe<DashboardGraphQLPayout>;
  utr?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};