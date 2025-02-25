import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMaybe, DashboardGraphQLMerchantAverageOrderFieldValue, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantAverageOrderField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'DashboardGraphQLMerchantAverageOrderField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  value?: DashboardGraphQLMaybe<DashboardGraphQLMerchantAverageOrderFieldValue>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};