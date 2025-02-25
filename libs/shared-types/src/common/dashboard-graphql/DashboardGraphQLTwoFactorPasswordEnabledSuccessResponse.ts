import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLTwoFactorPasswordEnabledSuccessResponse = {
  __typename?: 'DashboardGraphQLTwoFactorPasswordEnabledSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isPasswordEnabled: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};