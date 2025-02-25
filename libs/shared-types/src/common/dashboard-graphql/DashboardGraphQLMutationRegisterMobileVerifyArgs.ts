import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLCaptchaModeEnum, DashboardGraphQLPhoneInput, DashboardGraphQLUserSignupCampaignEnum } from './index';
export type DashboardGraphQLMutationRegisterMobileVerifyArgs = {
  captcha: DashboardGraphQLScalars['String'];
  captchaMode?: DashboardGraphQLInputMaybe<DashboardGraphQLCaptchaModeEnum>;
  contact: DashboardGraphQLPhoneInput;
  countryCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  otp: DashboardGraphQLScalars['String'];
  partnerReferralCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  referralCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  signupCampaign?: DashboardGraphQLInputMaybe<DashboardGraphQLUserSignupCampaignEnum>;
  source?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  token: DashboardGraphQLScalars['String'];
  utmCampaign?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  utmMedium?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  utmSource?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  workflowType?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};