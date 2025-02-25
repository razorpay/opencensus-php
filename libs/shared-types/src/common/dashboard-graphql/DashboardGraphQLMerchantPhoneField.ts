import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLPhone, DashboardGraphQLMaybe, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantPhoneField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'DashboardGraphQLMerchantPhoneField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  value: DashboardGraphQLPhone;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};