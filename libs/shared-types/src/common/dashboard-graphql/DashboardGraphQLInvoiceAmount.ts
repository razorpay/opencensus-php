import { DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLInvoiceAmount = {
  __typename?: 'DashboardGraphQLInvoiceAmount';
  due?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  generated: DashboardGraphQLMoney;
  paid?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
};