import { DashboardGraphQLMaybe, DashboardGraphQLAuthErrorCodeEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLAuthUnauthenticated = {
  __typename?: 'DashboardGraphQLAuthUnauthenticated';
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLAuthErrorCodeEnum>;
  message: DashboardGraphQLScalars['String'];
};