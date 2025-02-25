import { DashboardGraphQLScalars, DashboardGraphQLAadhaarDigilockerRedirectionUrlErrorTypeEnum, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerRedirectionUrlFailureResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerRedirectionUrlFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode: DashboardGraphQLAadhaarDigilockerRedirectionUrlErrorTypeEnum;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};