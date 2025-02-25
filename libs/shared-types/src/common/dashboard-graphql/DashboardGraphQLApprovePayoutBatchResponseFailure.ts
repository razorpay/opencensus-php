import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLApprovePayoutBatchResponseFailure = {
  __typename?: 'DashboardGraphQLApprovePayoutBatchResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  failedPayoutBatchIds?: DashboardGraphQLMaybe<Array<DashboardGraphQLScalars['ID']>>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};