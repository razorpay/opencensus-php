import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLSettlementAmount = {
  __typename?: 'DashboardGraphQLSettlementAmount';
  fee?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
  settlement: DashboardGraphQLMoney;
  tax?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
};