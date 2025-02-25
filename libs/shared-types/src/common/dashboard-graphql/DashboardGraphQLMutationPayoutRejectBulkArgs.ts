import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationPayoutRejectBulkArgs = {
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  ids: Array<DashboardGraphQLScalars['ID']>;
};