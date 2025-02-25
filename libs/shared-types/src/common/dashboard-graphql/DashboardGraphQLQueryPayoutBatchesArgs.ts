import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLPayoutBatchStatusEnum } from './index';
export type DashboardGraphQLQueryPayoutBatchesArgs = {
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  pendingOn?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  status?: DashboardGraphQLInputMaybe<DashboardGraphQLPayoutBatchStatusEnum>;
};