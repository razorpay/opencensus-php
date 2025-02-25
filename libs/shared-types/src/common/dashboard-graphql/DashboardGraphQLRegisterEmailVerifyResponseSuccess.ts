import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLUser } from './index';
export type DashboardGraphQLRegisterEmailVerifyResponseSuccess = {
  __typename?: 'DashboardGraphQLRegisterEmailVerifyResponseSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  user: DashboardGraphQLUser;
};