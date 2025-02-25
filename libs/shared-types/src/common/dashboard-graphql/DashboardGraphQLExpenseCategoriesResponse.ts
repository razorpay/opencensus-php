import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLExpenseCategory, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLExpenseCategoriesResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLExpenseCategoriesResponse';
  expenseCategories: Array<DashboardGraphQLExpenseCategory>;
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};