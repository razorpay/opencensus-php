import { DashboardGraphQLScalars, DashboardGraphQLLoginOtpErrorCodeEnum, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLLoginOtpResendError = {
  __typename?: 'DashboardGraphQLLoginOtpResendError';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode: DashboardGraphQLLoginOtpErrorCodeEnum;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};