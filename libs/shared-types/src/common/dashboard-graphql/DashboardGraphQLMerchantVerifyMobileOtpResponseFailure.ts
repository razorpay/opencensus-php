import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantVerifyMobileOtpResponseFailure = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'MerchantVerifyMobileOTPResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};