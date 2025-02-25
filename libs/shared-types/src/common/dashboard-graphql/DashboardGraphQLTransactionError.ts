import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLTransactionError = {
  __typename?: 'DashboardGraphQLTransactionError';
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  errorDescription?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  errorReason?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  errorSource?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  errorStep?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};