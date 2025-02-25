import { DashboardGraphQLMoneyInput, DashboardGraphQLPointOfSalePaymentTransactionInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationPointOfSalePaymentUpdateArgs = {
  amount: DashboardGraphQLMoneyInput;
  transaction: DashboardGraphQLPointOfSalePaymentTransactionInput;
  url: DashboardGraphQLScalars['URL'];
};