import { DashboardGraphQLMaybe, DashboardGraphQLMerchantWebsiteApplicationDetail, DashboardGraphQLScalars, DashboardGraphQLMerchantWebsiteApprovalStatusEnum, DashboardGraphQLMerchantWebsiteSectionStatusEnum } from './index';
export type DashboardGraphQLMerchantWebsiteSection = {
  __typename?: 'DashboardGraphQLMerchantWebsiteSection';
  appStore?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteApplicationDetail>>>;
  playStore?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteApplicationDetail>>>;
  publishedUrl?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  website?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteApplicationDetail>>>;
  websiteApprovalStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteApprovalStatusEnum>;
  websiteSectionStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteSectionStatusEnum>;
};