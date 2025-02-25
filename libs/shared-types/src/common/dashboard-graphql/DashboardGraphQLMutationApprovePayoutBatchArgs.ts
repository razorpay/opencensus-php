import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationApprovePayoutBatchArgs = {
  batchIds: Array<DashboardGraphQLScalars['ID']>;
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  otp: DashboardGraphQLScalars['String'];
  token: DashboardGraphQLScalars['String'];
};