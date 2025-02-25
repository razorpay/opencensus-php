import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLUser } from './index';
export type DashboardGraphQLTwoFactorPasswordCreateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLTwoFactorPasswordCreateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  user: DashboardGraphQLUser;
};