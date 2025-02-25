import { DashboardGraphQLMerchantBankingAccount, DashboardGraphQLPayoutsPendingSummary, DashboardGraphQLPayoutsQueuedSummary, DashboardGraphQLPayoutsScheduledSummary } from './index';
export type DashboardGraphQLPayoutsSummary = {
  __typename?: 'DashboardGraphQLPayoutsSummary';
  bankingAccount: DashboardGraphQLMerchantBankingAccount;
  pending: DashboardGraphQLPayoutsPendingSummary;
  queued: Array<DashboardGraphQLPayoutsQueuedSummary>;
  scheduled: Array<DashboardGraphQLPayoutsScheduledSummary>;
};