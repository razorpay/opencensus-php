import { DashboardGraphQLTwoFactorActionTypeEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationTwoFactorEmailOtpVerifyArgs = {
  action: DashboardGraphQLTwoFactorActionTypeEnum;
  otp: DashboardGraphQLScalars['String'];
  token: DashboardGraphQLScalars['String'];
};