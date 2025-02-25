import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationCreateWithdrawalArgs = {
  amount: DashboardGraphQLScalars['Int'];
  drawn_at: DashboardGraphQLScalars['String'];
  due_date: DashboardGraphQLScalars['String'];
  message: DashboardGraphQLScalars['String'];
  owner_id: DashboardGraphQLScalars['String'];
  owner_type: DashboardGraphQLScalars['String'];
  start_date: DashboardGraphQLScalars['String'];
  tenure: DashboardGraphQLScalars['Int'];
  withdrawal_config_id: DashboardGraphQLScalars['String'];
};