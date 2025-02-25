import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerRedirectionUrlSuccessResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerRedirectionUrlSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  redirectionUrl: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};