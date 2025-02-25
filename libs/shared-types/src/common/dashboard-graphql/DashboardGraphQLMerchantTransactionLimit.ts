import { DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLMerchantTransactionLimit = {
  __typename?: 'DashboardGraphQLMerchantTransactionLimit';
  paymentLimit?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  settlementLimit: DashboardGraphQLMoney;
};