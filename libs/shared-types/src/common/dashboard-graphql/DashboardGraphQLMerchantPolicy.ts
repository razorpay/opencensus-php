import { DashboardGraphQLMaybe, DashboardGraphQLMerchantWebsiteVerficationSection } from './index';
export type DashboardGraphQLMerchantPolicy = {
  __typename?: 'DashboardGraphQLMerchantPolicy';
  contactUs?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteVerficationSection>;
  privacy?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteVerficationSection>;
  refund?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteVerficationSection>;
  shipping?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteVerficationSection>;
  termsAndConditions?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteVerficationSection>;
};