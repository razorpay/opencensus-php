import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantSocialMediaUrlInputField, DashboardGraphQLMerchantUrlInputField } from './index';
export type DashboardGraphQLMerchantAcceptanceChannelInput = {
  accept?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  complianceConsent?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  socialMediaUrls?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLMerchantSocialMediaUrlInputField>>;
  urls?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLInputMaybe<DashboardGraphQLMerchantUrlInputField>>>;
  value?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};