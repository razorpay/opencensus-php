import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentInstantRefundEligibilityAmount = {
  __typename?: 'DashboardGraphQLPaymentInstantRefundEligibilityAmount';
  amount: DashboardGraphQLMoney;
  fee?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
  tax?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
};