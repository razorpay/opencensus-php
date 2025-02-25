import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLPayoutModeEnum, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationPayoutCreateIciciArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  fundAccountId: DashboardGraphQLScalars['String'];
  mode: DashboardGraphQLPayoutModeEnum;
  narration?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  purpose: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Boolean'];
};