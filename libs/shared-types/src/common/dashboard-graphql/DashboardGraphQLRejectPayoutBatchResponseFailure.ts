import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLRejectPayoutBatchResponseFailure = {
  __typename?: 'DashboardGraphQLRejectPayoutBatchResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  failedPayoutBatchIds?: DashboardGraphQLMaybe<Array<DashboardGraphQLScalars['ID']>>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};