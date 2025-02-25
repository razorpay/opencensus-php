import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLPayoutModeEnum, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationPayoutCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  fundAccountId: DashboardGraphQLScalars['String'];
  mode: DashboardGraphQLPayoutModeEnum;
  narration?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  otp: DashboardGraphQLScalars['String'];
  purpose: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Int'];
  token: DashboardGraphQLScalars['String'];
};