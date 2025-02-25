import { DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLVendorPaymentPayoutAmounts = {
  __typename?: 'DashboardGraphQLVendorPaymentPayoutAmounts';
  paidAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  pendingAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  processingAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  scheduledAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
};