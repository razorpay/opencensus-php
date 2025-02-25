import { DashboardGraphQLCurrency, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMoney = {
  __typename?: 'DashboardGraphQLMoney';
  currency: DashboardGraphQLCurrency;
  value: DashboardGraphQLScalars['BigInt'];
};