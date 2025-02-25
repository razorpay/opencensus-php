import { DashboardGraphQLInputMaybe, DashboardGraphQLCountryCodeEnum, DashboardGraphQLScalars, DashboardGraphQLClientPlatformEnum, DashboardGraphQLOAuthProviderEnum, DashboardGraphQLUserSignupCampaignEnum } from './index';
export type DashboardGraphQLMutationRegisterOAuthArgs = {
  countryCode?: DashboardGraphQLInputMaybe<DashboardGraphQLCountryCodeEnum>;
  email: DashboardGraphQLScalars['EmailAddress'];
  idToken: DashboardGraphQLScalars['String'];
  partnerIntent?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  platform: DashboardGraphQLClientPlatformEnum;
  provider: DashboardGraphQLOAuthProviderEnum;
  signupCampaign?: DashboardGraphQLInputMaybe<DashboardGraphQLUserSignupCampaignEnum>;
  workflowType?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};