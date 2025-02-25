import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLLoginOtpResendSuccess = {
  __typename?: 'DashboardGraphQLLoginOtpResendSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
};