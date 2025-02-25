import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLPayoutFeeEnum } from './index';
export type DashboardGraphQLPayoutFee = {
  __typename?: 'DashboardGraphQLPayoutFee';
  amount: DashboardGraphQLMoney;
  type?: DashboardGraphQLMaybe<DashboardGraphQLPayoutFeeEnum>;
};