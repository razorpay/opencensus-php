import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantWebsiteDetailsSubmitEnum, DashboardGraphQLMerchantWebsiteApplicationInput, DashboardGraphQLMerchantWebsiteSectionStatusEnum } from './index';
export type DashboardGraphQLMerchantWebsiteSectionInput = {
  status?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteDetailsSubmitEnum>;
  website?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteApplicationInput>;
  websiteSectionStatus?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteSectionStatusEnum>;
};