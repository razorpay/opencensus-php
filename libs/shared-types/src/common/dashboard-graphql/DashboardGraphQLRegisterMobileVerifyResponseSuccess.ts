import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLUser } from './index';
export type DashboardGraphQLRegisterMobileVerifyResponseSuccess = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLRegisterMobileVerifyResponseSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  user: DashboardGraphQLUser;
};