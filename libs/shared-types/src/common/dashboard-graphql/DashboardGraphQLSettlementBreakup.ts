import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLSettlementBreakupComponentEnum, DashboardGraphQLScalars, DashboardGraphQLSettlementBreakupTransactionTypeEnum } from './index';
export type DashboardGraphQLSettlementBreakup = {
  __typename?: 'DashboardGraphQLSettlementBreakup';
  amount: DashboardGraphQLMoney;
  component?: DashboardGraphQLMaybe<DashboardGraphQLSettlementBreakupComponentEnum>;
  count?: DashboardGraphQLMaybe<DashboardGraphQLScalars['PositiveInt']>;
  transactionType?: DashboardGraphQLMaybe<DashboardGraphQLSettlementBreakupTransactionTypeEnum>;
};