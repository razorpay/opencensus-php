import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLRegisterMobileVerifyEnum } from './index';
export type DashboardGraphQLRegisterMobileVerifyResponseFailure = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLRegisterMobileVerifyResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLRegisterMobileVerifyEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};