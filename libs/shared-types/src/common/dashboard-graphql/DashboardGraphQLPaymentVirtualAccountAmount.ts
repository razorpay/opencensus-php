import { DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLPaymentVirtualAccountAmount = {
  __typename?: 'DashboardGraphQLPaymentVirtualAccountAmount';
  expected?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  paid: DashboardGraphQLMoney;
};