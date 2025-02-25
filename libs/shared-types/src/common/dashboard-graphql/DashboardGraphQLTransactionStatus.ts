import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLTransactionStatus = {
  __typename?: 'DashboardGraphQLTransactionStatus';
  captured?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};