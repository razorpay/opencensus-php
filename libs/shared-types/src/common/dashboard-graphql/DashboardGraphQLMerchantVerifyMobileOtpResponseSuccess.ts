import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantVerifyMobileOtpResponseSuccess = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'MerchantVerifyMobileOTPResponseSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  contact: DashboardGraphQLScalars['String'];
  isContactNumberVerified: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};