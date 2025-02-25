import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLRepaymentsBreakup = {
  __typename?: 'DashboardGraphQLRepaymentsBreakup';
  interest?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  principal?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  total_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  withdrawal_id?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  withdrawn_at?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};