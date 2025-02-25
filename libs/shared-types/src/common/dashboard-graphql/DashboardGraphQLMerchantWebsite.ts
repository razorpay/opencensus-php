import { DashboardGraphQLMaybe, DashboardGraphQLMerchantWebsiteAdditionalData, DashboardGraphQLMerchantWebsiteSection, DashboardGraphQLMerchantWebsiteApprovalStatusEnum, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantWebsite = {
  __typename?: 'DashboardGraphQLMerchantWebsite';
  additionalData?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteAdditionalData>;
  contactUs?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteSection>;
  privacy?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteSection>;
  refund?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteSection>;
  shipping?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteSection>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteApprovalStatusEnum>;
  termsAndConditions?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteSection>;
  websitePolicyVerificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};