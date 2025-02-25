import { DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutsQueuedSummaryNeftLimitExhausted = {
  __typename?: 'PayoutsQueuedSummaryNEFTLimitExhausted';
  balance: DashboardGraphQLMoney;
  count: DashboardGraphQLScalars['NonNegativeInt'];
  totalAmount: DashboardGraphQLMoney;
  totalFees: DashboardGraphQLMoney;
};