import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLCaptchaModeEnum, DashboardGraphQLCountryCodeEnum, DashboardGraphQLUserSignupCampaignEnum, DashboardGraphQLRegisterEmailVerificationMethodEnum } from './index';
export type DashboardGraphQLMutationRegisterEmailArgs = {
  captcha?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  captchaMode?: DashboardGraphQLInputMaybe<DashboardGraphQLCaptchaModeEnum>;
  confirmPassword: DashboardGraphQLScalars['String'];
  countryCode?: DashboardGraphQLInputMaybe<DashboardGraphQLCountryCodeEnum>;
  email: DashboardGraphQLScalars['EmailAddress'];
  partnerIntent?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  password: DashboardGraphQLScalars['String'];
  signupCampaign?: DashboardGraphQLInputMaybe<DashboardGraphQLUserSignupCampaignEnum>;
  verificationMethod: DashboardGraphQLRegisterEmailVerificationMethodEnum;
  workflowType?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};