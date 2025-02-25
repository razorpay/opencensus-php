import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLBudgetStatusEnum } from './index';
export type DashboardGraphQLQueryBudgetsArgs = {
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  statuses?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLBudgetStatusEnum>>;
};