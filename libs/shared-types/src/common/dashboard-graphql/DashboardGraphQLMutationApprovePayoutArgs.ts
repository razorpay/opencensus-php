import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationApprovePayoutArgs = {
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  otp: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Int'];
  token: DashboardGraphQLScalars['String'];
};