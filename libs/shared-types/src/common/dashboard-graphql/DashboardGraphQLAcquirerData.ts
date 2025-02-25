import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLAcquirerData = {
  __typename?: 'DashboardGraphQLAcquirerData';
  arn?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  rrn?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  utr?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};