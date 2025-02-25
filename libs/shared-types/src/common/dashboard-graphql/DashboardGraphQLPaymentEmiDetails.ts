import { DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentEmiDetails = {
  __typename?: 'DashboardGraphQLPaymentEmiDetails';
  amount: DashboardGraphQLMoney;
  duration: DashboardGraphQLScalars['Float'];
  rate: DashboardGraphQLScalars['Float'];
};