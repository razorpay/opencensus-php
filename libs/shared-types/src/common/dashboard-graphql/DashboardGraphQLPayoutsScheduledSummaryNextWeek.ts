import { DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutsScheduledSummaryNextWeek = {
  __typename?: 'DashboardGraphQLPayoutsScheduledSummaryNextWeek';
  balance: DashboardGraphQLMoney;
  count: DashboardGraphQLScalars['NonNegativeInt'];
  totalAmount: DashboardGraphQLMoney;
  totalFees: DashboardGraphQLMoney;
};