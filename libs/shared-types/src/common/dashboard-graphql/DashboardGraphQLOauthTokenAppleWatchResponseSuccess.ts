import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLOauthTokenAppleWatchResponseSuccess = {
  __typename?: 'DashboardGraphQLOauthTokenAppleWatchResponseSuccess';
  accessToken: DashboardGraphQLScalars['String'];
  accountId: DashboardGraphQLScalars['String'];
  code: DashboardGraphQLScalars['PositiveInt'];
  expiresIn: DashboardGraphQLScalars['DateTime'];
  publicToken: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
  tokenType: DashboardGraphQLScalars['String'];
};