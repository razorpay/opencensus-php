import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLOauthTokenAppleWatchOtp = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOauthTokenAppleWatchOtp';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
};