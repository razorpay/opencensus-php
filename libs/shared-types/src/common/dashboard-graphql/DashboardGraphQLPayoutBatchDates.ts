import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPayoutBatchDates = {
  __typename?: 'DashboardGraphQLPayoutBatchDates';
  createdAt: DashboardGraphQLScalars['DateTime'];
  failedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  pendingAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  processedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  processingAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  rejectedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  validatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  validatingAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};