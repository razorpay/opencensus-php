import { DashboardGraphQLScalars, DashboardGraphQLAadhaarOtpDigilockerErrorEnum, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerOtpFailureResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerOtpFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode: DashboardGraphQLAadhaarOtpDigilockerErrorEnum;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};