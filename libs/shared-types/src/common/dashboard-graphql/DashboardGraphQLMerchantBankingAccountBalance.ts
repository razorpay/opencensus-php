import { DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantBankingAccountBalance = {
  __typename?: 'DashboardGraphQLMerchantBankingAccountBalance';
  amount: DashboardGraphQLMoney;
  id: DashboardGraphQLScalars['ID'];
  lastCheckedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};