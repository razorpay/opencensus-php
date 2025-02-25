import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLLoginOtpErrorCodeEnum } from './index';
export type DashboardGraphQLLoginOtpError = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLLoginOtpError';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLLoginOtpErrorCodeEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};