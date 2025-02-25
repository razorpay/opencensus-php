import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAccountVerificationOtpResponse = {
  __typename?: 'DashboardGraphQLAccountVerificationOtpResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};