import { DashboardGraphQLMaybe, DashboardGraphQLEmiSummaryCharges, DashboardGraphQLScalars, DashboardGraphQLEmiSummaryDetails } from './index';
export type DashboardGraphQLEmiSummary = {
  __typename?: 'EMISummary';
  charges?: DashboardGraphQLMaybe<Array<DashboardGraphQLEmiSummaryCharges>>;
  emi_end_date?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  emi_start_date?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  emis?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLEmiSummaryDetails>>>;
  first_emi_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  pre_emi_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  pre_emi_days?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_emi_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_emi_days?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_interest?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};