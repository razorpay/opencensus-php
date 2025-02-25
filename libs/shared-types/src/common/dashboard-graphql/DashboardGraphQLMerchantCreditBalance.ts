import { DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantCreditBalance = {
  __typename?: 'DashboardGraphQLMerchantCreditBalance';
  amountCredits: DashboardGraphQLMoney;
  balanceId: DashboardGraphQLScalars['ID'];
  feeCredits: DashboardGraphQLMoney;
  refundCredits: DashboardGraphQLMoney;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};