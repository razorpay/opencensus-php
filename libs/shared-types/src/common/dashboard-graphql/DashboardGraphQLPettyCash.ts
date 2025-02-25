import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLBudget, DashboardGraphQLUser, DashboardGraphQLPettyCashDate, DashboardGraphQLPettyCashDestinationDetails, DashboardGraphQLExpenseCategory, DashboardGraphQLScalars, DashboardGraphQLPettyCashModeEnum, DashboardGraphQLPayout, DashboardGraphQLPettyCashStatusEnum } from './index';
export type DashboardGraphQLPettyCash = {
  __typename?: 'DashboardGraphQLPettyCash';
  amount: DashboardGraphQLMoney;
  budget?: DashboardGraphQLMaybe<DashboardGraphQLBudget>;
  createdBy: DashboardGraphQLUser;
  dates: DashboardGraphQLPettyCashDate;
  destinationAccount: DashboardGraphQLPettyCashDestinationDetails;
  expenseCategory?: DashboardGraphQLMaybe<DashboardGraphQLExpenseCategory>;
  id: DashboardGraphQLScalars['ID'];
  mode?: DashboardGraphQLMaybe<DashboardGraphQLPettyCashModeEnum>;
  narration?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  payout?: DashboardGraphQLMaybe<DashboardGraphQLPayout>;
  purpose?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  status: DashboardGraphQLPettyCashStatusEnum;
};