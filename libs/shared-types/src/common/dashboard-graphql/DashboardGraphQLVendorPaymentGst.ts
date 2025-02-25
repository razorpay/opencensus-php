import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLGstTypeEnum } from './index';
export type DashboardGraphQLVendorPaymentGst = {
  __typename?: 'VendorPaymentGST';
  amount: DashboardGraphQLMoney;
  gstin?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  type: DashboardGraphQLGstTypeEnum;
};