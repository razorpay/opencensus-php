import { DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMaybe, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantFieldInterface = {
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};