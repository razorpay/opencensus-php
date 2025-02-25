import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLOauthTokenAppleWatchResponseError = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOauthTokenAppleWatchResponseError';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};