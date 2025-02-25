import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLSendEmailVerificationOtpEnum } from './index';
export type DashboardGraphQLSendEmailVerificationOtpErrorResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSendEmailVerificationOtpErrorResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLSendEmailVerificationOtpEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};