import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLSettlementStatusEnum } from './index';
export type DashboardGraphQLQuerySettlementsArgs = {
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  status?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLSettlementStatusEnum>>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
};