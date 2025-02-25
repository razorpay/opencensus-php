import { DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLOrderAmount = {
  __typename?: 'DashboardGraphQLOrderAmount';
  due?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  generated: DashboardGraphQLMoney;
  paid?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
};