import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPhone } from './index';
export type DashboardGraphQLTwoFactorAddMobileOtpSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLTwoFactorAddMobileOtpSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  phone: DashboardGraphQLPhone;
  success: DashboardGraphQLScalars['Boolean'];
};