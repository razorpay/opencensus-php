import { DashboardGraphQLMoneyInput, DashboardGraphQLPaymentApplicationEnum, DashboardGraphQLPaymentMethodEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationPointOfSalePaymentCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  application: DashboardGraphQLPaymentApplicationEnum;
  method: DashboardGraphQLPaymentMethodEnum;
  orderId: DashboardGraphQLScalars['ID'];
};