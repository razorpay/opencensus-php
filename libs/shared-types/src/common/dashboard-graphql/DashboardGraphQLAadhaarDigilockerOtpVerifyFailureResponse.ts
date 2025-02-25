import { DashboardGraphQLScalars, DashboardGraphQLAadhaarOtpDigilockerErrorEnum, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerOtpVerifyFailureResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerOtpVerifyFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode: DashboardGraphQLAadhaarOtpDigilockerErrorEnum;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};