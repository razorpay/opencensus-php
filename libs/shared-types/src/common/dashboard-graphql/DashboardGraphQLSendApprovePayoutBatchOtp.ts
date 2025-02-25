import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSendApprovePayoutBatchOtp = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSendApprovePayoutBatchOtp';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};