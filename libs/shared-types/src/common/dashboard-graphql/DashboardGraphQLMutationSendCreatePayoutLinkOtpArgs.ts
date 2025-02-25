import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationSendCreatePayoutLinkOtpArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  purpose: DashboardGraphQLScalars['String'];
};