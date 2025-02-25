import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentAmount = {
  __typename?: 'DashboardGraphQLPaymentAmount';
  charged: DashboardGraphQLMoney;
  /** converted = charged * exchange_rate */
  converted?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  fee?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
  tax?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
};