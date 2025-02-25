import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantNumberField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'DashboardGraphQLMerchantNumberField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  value?: DashboardGraphQLMaybe<DashboardGraphQLScalars['PositiveInt']>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};