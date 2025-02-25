import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationAccountVerifyArgs = {
  captcha?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  otp: DashboardGraphQLScalars['String'];
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  token: DashboardGraphQLScalars['String'];
};