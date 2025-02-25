import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerRedirectionVerificationSuccessResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerRedirectionVerificationSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isValid: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};