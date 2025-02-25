import { DashboardGraphQLMerchantCreditBalance, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantCreditBalanceSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantCreditBalanceSuccessResponse';
  balanceDetails: DashboardGraphQLMerchantCreditBalance;
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};