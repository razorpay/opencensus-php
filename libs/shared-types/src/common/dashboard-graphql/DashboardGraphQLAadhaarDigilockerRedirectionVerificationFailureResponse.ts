import { DashboardGraphQLScalars, DashboardGraphQLAadhaarDigilockerRedirectionVerificationErrorTypeEnum, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerRedirectionVerificationFailureResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerRedirectionVerificationFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode: DashboardGraphQLAadhaarDigilockerRedirectionVerificationErrorTypeEnum;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};