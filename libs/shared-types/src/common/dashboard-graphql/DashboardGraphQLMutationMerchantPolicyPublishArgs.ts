import { DashboardGraphQLMerchantWebsiteActionEnum, DashboardGraphQLMerchantWebsiteSectionEnum, DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationMerchantPolicyPublishArgs = {
  action: DashboardGraphQLMerchantWebsiteActionEnum;
  section: Array<DashboardGraphQLMerchantWebsiteSectionEnum>;
  submit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
};