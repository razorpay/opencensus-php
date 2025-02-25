import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLConfiguration = {
  __typename?: 'DashboardGraphQLConfiguration';
  business_type?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  credit_limit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  end_day_limit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  interest?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  internal_credit_limit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  max_withdraw_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  min_withdraw_amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  start_day_limit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};