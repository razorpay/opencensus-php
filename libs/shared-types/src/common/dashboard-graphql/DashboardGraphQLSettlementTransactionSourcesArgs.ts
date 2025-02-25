import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLSettlementBreakupComponentEnum } from './index';
export type DashboardGraphQLSettlementTransactionSourcesArgs = {
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  sourceType: DashboardGraphQLSettlementBreakupComponentEnum;
};