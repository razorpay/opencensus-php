import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationPayoutApproveBulkArgs = {
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  ids: Array<DashboardGraphQLScalars['ID']>;
  otp: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
};