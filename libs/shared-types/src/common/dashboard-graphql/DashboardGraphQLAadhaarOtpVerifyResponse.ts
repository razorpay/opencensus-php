import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLAadhaarOtpVerifyErrorTypeEnum } from './index';
export type DashboardGraphQLAadhaarOtpVerifyResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLAadhaarOtpVerifyResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLAadhaarOtpVerifyErrorTypeEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};