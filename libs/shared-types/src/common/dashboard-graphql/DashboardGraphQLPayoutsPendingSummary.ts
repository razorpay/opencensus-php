import { DashboardGraphQLScalars, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLPayoutsPendingSummary = {
  __typename?: 'DashboardGraphQLPayoutsPendingSummary';
  count: DashboardGraphQLScalars['NonNegativeInt'];
  totalAmount: DashboardGraphQLMoney;
};