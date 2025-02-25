import { DashboardGraphQLScalars, DashboardGraphQLMerchantVirtualAccountReceiver, DashboardGraphQLMerchantVirtualAccountStatus } from './index';
export type DashboardGraphQLMerchantVirtualAccount = {
  __typename?: 'DashboardGraphQLMerchantVirtualAccount';
  id: DashboardGraphQLScalars['ID'];
  receivers: Array<DashboardGraphQLMerchantVirtualAccountReceiver>;
  status: DashboardGraphQLMerchantVirtualAccountStatus;
};