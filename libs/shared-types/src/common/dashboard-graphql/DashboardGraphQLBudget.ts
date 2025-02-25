import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLUser, DashboardGraphQLBudgetDate, DashboardGraphQLBudgetStatusEnum, DashboardGraphQLBudgetTypeEnum } from './index';
export type DashboardGraphQLBudget = {
  __typename?: 'DashboardGraphQLBudget';
  availableAmount: DashboardGraphQLMoney;
  balanceId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  createdBy: DashboardGraphQLUser;
  dates: DashboardGraphQLBudgetDate;
  id: DashboardGraphQLScalars['ID'];
  isRecurring?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  name: DashboardGraphQLScalars['String'];
  status: DashboardGraphQLBudgetStatusEnum;
  type: DashboardGraphQLBudgetTypeEnum;
};