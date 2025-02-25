import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLTwoFactorAddMobileOtpErrorResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLTwoFactorAddMobileOtpErrorResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};