import { DashboardGraphQLMerchantFieldInterface, DashboardGraphQLMerchantFieldClarificationReason, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantStringField = DashboardGraphQLMerchantFieldInterface & {
  __typename?: 'DashboardGraphQLMerchantStringField';
  clarificationReasons: Array<DashboardGraphQLMerchantFieldClarificationReason>;
  suggestedValue?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  value?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  verificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};