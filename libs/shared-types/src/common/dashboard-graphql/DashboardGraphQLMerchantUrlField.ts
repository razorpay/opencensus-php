import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantUrlField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'MerchantURLField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  value?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};