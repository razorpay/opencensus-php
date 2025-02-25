import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLBudgetDate = {
  __typename?: 'DashboardGraphQLBudgetDate';
  createdAt: DashboardGraphQLScalars['DateTime'];
  endAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  startAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};