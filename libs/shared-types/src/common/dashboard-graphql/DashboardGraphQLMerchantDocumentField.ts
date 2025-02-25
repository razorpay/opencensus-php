import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMerchantDocumentFieldValue, DashboardGraphQLMaybe, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantDocumentField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'DashboardGraphQLMerchantDocumentField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  values: Array<DashboardGraphQLMerchantDocumentFieldValue>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};