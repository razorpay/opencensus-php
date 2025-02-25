import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantVirtualAccountStatus, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantBalanceVirtualAccountsResponseArgs = {
  virtualAccountStatus?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantVirtualAccountStatus>;
  virtualAccountsLimit: DashboardGraphQLScalars['PositiveInt'];
  virtualAccountsOffset: DashboardGraphQLScalars['NonNegativeInt'];
};