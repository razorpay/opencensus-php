import { DashboardGraphQLMoney, DashboardGraphQLTdsCategory } from './index';
export type DashboardGraphQLVendorPaymentTds = {
  __typename?: 'VendorPaymentTDS';
  amount: DashboardGraphQLMoney;
  deductedAmount: DashboardGraphQLMoney;
  tdsCategory: DashboardGraphQLTdsCategory;
};