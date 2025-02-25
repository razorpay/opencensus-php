import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLBudget, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLBudgetsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLBudgetsResponse';
  budgets: Array<DashboardGraphQLBudget>;
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};