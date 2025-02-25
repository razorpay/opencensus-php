import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantWebsiteAdditionalDataInput, DashboardGraphQLMerchantWebsiteSectionInput, DashboardGraphQLMerchantWebsiteDetailsSubmitEnum } from './index';
export type DashboardGraphQLMutationMerchantWebsiteDetailsUpdateArgs = {
  additionalData?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteAdditionalDataInput>;
  contactUs?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteSectionInput>;
  privacy?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteSectionInput>;
  refund?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteSectionInput>;
  shipping?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteSectionInput>;
  status?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteDetailsSubmitEnum>;
  termsAndConditions?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantWebsiteSectionInput>;
};