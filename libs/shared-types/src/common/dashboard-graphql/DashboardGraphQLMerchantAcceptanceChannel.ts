import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantSocialMediaUrlField, DashboardGraphQLMerchantUrlField } from './index';
export type DashboardGraphQLMerchantAcceptanceChannel = {
  __typename?: 'DashboardGraphQLMerchantAcceptanceChannel';
  accept: DashboardGraphQLScalars['Boolean'];
  complianceConsent?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  socialMediaUrls: Array<DashboardGraphQLMerchantSocialMediaUrlField>;
  urls: Array<DashboardGraphQLMerchantUrlField>;
  value?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};