import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarDigilockerOtpSuccessResponse = {
  __typename?: 'DashboardGraphQLAadhaarDigilockerOtpSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  requestId: DashboardGraphQLScalars['ID'];
  success: DashboardGraphQLScalars['Boolean'];
};