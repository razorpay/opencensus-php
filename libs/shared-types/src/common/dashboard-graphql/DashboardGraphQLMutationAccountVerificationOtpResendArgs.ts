import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationAccountVerificationOtpResendArgs = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  password: DashboardGraphQLScalars['String'];
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  token: DashboardGraphQLScalars['String'];
};