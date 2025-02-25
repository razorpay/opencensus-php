import { DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutsQueuedSummaryLowBalance = {
  __typename?: 'DashboardGraphQLPayoutsQueuedSummaryLowBalance';
  balance: DashboardGraphQLMoney;
  count: DashboardGraphQLScalars['NonNegativeInt'];
  totalAmount: DashboardGraphQLMoney;
  totalFees: DashboardGraphQLMoney;
};