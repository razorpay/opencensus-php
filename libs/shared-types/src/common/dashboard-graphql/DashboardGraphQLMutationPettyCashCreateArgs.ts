import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLDestinationAccountDetailsInput, DashboardGraphQLPettyCashModeEnum, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationPettyCashCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  budgetId: DashboardGraphQLScalars['String'];
  destinationAccountDetails: DashboardGraphQLDestinationAccountDetailsInput;
  expenseCategoryId: DashboardGraphQLScalars['String'];
  mode: DashboardGraphQLPettyCashModeEnum;
  narration?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  otp: DashboardGraphQLScalars['String'];
  purpose: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
};