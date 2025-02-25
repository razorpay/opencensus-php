import { DashboardGraphQLWithdrawalListStatus, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLWithdrawalsList = {
  __typename?: 'DashboardGraphQLWithdrawalsList';
  status: DashboardGraphQLWithdrawalListStatus;
  total_outstanding_balance?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  withdrawal_amount: DashboardGraphQLScalars['Int'];
  withdrawal_id: DashboardGraphQLScalars['String'];
  withdrawn_at: DashboardGraphQLScalars['String'];
};