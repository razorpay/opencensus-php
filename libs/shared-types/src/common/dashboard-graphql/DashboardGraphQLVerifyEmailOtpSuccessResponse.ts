import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLVerifyEmailOtpSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLVerifyEmailOtpSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};