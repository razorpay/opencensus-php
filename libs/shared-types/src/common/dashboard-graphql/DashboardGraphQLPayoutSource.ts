import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutSource = {
  __typename?: 'DashboardGraphQLPayoutSource';
  priority?: DashboardGraphQLMaybe<DashboardGraphQLScalars['BigInt']>;
  sourceId: DashboardGraphQLScalars['String'];
  sourceType: DashboardGraphQLScalars['String'];
};