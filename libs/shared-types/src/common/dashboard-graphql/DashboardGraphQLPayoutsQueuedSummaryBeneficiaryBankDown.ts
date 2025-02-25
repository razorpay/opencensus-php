import { DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutsQueuedSummaryBeneficiaryBankDown = {
  __typename?: 'DashboardGraphQLPayoutsQueuedSummaryBeneficiaryBankDown';
  balance: DashboardGraphQLMoney;
  count: DashboardGraphQLScalars['NonNegativeInt'];
  totalAmount: DashboardGraphQLMoney;
  totalFees: DashboardGraphQLMoney;
};