import { DashboardGraphQLScalars, DashboardGraphQLClientPlatformEnum, DashboardGraphQLOAuthProviderEnum } from './index';
export type DashboardGraphQLMutationLoginOAuthArgs = {
  email: DashboardGraphQLScalars['EmailAddress'];
  idToken: DashboardGraphQLScalars['String'];
  platform: DashboardGraphQLClientPlatformEnum;
  provider: DashboardGraphQLOAuthProviderEnum;
};