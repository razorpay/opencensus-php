import { DashboardGraphQLMoney } from './index';
export type DashboardGraphQLTransactionAmount = {
  __typename?: 'DashboardGraphQLTransactionAmount';
  amount: DashboardGraphQLMoney;
  amountRefunded: DashboardGraphQLMoney;
  amountTransferred: DashboardGraphQLMoney;
  baseAmount: DashboardGraphQLMoney;
};