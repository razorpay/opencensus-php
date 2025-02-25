import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLPhoneInput, DashboardGraphQLPayoutLinkSendVia } from './index';
export type DashboardGraphQLMutationPayoutLinkCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  description: DashboardGraphQLScalars['String'];
  merchantContactEmail?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  merchantContactId: DashboardGraphQLScalars['String'];
  merchantContactPhone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  otp: DashboardGraphQLScalars['String'];
  purpose: DashboardGraphQLScalars['String'];
  referenceId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  sendVia: DashboardGraphQLPayoutLinkSendVia;
  token: DashboardGraphQLScalars['String'];
};