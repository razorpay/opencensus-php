import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLUserOtpVerifyErrorTypeEnum } from './index';
export type DashboardGraphQLUserOtpVerifyResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLUserOtpVerifyResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLUserOtpVerifyErrorTypeEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};