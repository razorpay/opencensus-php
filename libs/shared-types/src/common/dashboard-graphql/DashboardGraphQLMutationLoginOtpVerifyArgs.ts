import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLCaptchaModeEnum, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationLoginOtpVerifyArgs = {
  captcha?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  captchaMode?: DashboardGraphQLInputMaybe<DashboardGraphQLCaptchaModeEnum>;
  clientId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  onboardingSignature?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  otp: DashboardGraphQLScalars['String'];
  partnerId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  phone: DashboardGraphQLPhoneInput;
  token: DashboardGraphQLScalars['String'];
};