import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLSettlementCycle = {
  __typename?: 'DashboardGraphQLSettlementCycle';
  accountBalance: DashboardGraphQLMoney;
  amountToBeSettled: DashboardGraphQLMoney;
  isOnHold?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  nextSettlementOn?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  reason?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};