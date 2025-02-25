import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLResendTwoFactorLoginOtpResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLResendTwoFactorLoginOtpResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};