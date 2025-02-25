import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationAccountVerificationOtpArgs = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  password: DashboardGraphQLScalars['String'];
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
};