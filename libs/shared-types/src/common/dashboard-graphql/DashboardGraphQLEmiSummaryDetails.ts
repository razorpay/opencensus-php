import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLEmiSummaryDetails = {
  __typename?: 'DashboardGraphQLEmiSummaryDetails';
  due_date?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  interest_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  principal_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};