import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLUserAuthentication = {
  __typename?: 'DashboardGraphQLUserAuthentication';
  isAuthenticated: DashboardGraphQLScalars['Boolean'];
  merchantId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  userId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};