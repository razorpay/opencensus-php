import { DashboardGraphQLMaybe, DashboardGraphQLWithdrawal } from './index';
export type DashboardGraphQLCreateWithdrawalResponse = {
  __typename?: 'createWithdrawalResponse';
  withdrawal?: DashboardGraphQLMaybe<DashboardGraphQLWithdrawal>;
};