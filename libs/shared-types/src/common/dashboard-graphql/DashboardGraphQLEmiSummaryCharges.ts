import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLEmiSummaryCharges = {
  __typename?: 'DashboardGraphQLEmiSummaryCharges';
  charge_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  charge_head?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tax_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};