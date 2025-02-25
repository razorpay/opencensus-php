import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSendApprovePayoutOtp = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSendApprovePayoutOtp';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};