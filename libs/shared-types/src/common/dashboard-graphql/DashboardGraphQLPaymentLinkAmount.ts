import { DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLPaymentLinkAmount = {
  __typename?: 'DashboardGraphQLPaymentLinkAmount';
  due?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  firstMinimumPartialAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  generated: DashboardGraphQLMoney;
  paid?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
};