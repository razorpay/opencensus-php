import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationRejectPayoutBatchArgs = {
  batchIds: Array<DashboardGraphQLScalars['ID']>;
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};