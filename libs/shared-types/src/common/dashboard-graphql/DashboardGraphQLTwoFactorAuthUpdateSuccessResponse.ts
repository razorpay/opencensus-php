import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLTwoFactorAuthUpdateSuccessResponse = {
  __typename?: 'DashboardGraphQLTwoFactorAuthUpdateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isTwoFactorEnabled: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};