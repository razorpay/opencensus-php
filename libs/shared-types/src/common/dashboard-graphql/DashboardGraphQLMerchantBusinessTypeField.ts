import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMaybe, DashboardGraphQLMerchantBusinessTypeEnum, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantBusinessTypeField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'DashboardGraphQLMerchantBusinessTypeField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  value?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBusinessTypeEnum>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};