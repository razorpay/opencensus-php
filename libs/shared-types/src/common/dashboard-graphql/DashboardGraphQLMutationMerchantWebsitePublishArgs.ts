import { DashboardGraphQLMerchantWebsiteActionEnum, DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantWebsiteSectionEnum } from './index';
export type DashboardGraphQLMutationMerchantWebsitePublishArgs = {
  action: DashboardGraphQLMerchantWebsiteActionEnum;
  consentUrl?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['URL']>;
  hasMerchantConsent: DashboardGraphQLScalars['Boolean'];
  section: DashboardGraphQLMerchantWebsiteSectionEnum;
};