import { DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutsScheduledSummaryNextMonth = {
  __typename?: 'DashboardGraphQLPayoutsScheduledSummaryNextMonth';
  balance: DashboardGraphQLMoney;
  count: DashboardGraphQLScalars['NonNegativeInt'];
  totalAmount: DashboardGraphQLMoney;
  totalFees: DashboardGraphQLMoney;
};