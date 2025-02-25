import { DashboardGraphQLSettlementAmount, DashboardGraphQLMaybe, DashboardGraphQLSettlementBreakup, DashboardGraphQLScalars, DashboardGraphQLSettlementStatusEnum, DashboardGraphQLTransactionSourceDetails } from './index';
export type DashboardGraphQLSettlement = {
  __typename?: 'DashboardGraphQLSettlement';
  amount: DashboardGraphQLSettlementAmount;
  breakUp?: DashboardGraphQLMaybe<Array<DashboardGraphQLSettlementBreakup>>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  id: DashboardGraphQLScalars['ID'];
  status: DashboardGraphQLSettlementStatusEnum;
  transactionSources?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLTransactionSourceDetails>>>;
  /** Unique DashboardGraphQLTransaction Reference number available across banks */
  utr?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};