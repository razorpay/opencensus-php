import { DashboardGraphQLMaybe, DashboardGraphQLMerchantBalanceAccountTypeEnum, DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLMerchantBalanceProductTypeEnum, DashboardGraphQLMerchantVirtualAccountsResponse } from './index';
export type DashboardGraphQLMerchantBalance = {
  __typename?: 'DashboardGraphQLMerchantBalance';
  accountType?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBalanceAccountTypeEnum>;
  amount: DashboardGraphQLMoney;
  id: DashboardGraphQLScalars['ID'];
  productType: DashboardGraphQLMerchantBalanceProductTypeEnum;
  virtualAccountsResponse?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVirtualAccountsResponse>;
};