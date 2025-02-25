import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutDate = {
  __typename?: 'DashboardGraphQLPayoutDate';
  cancelledAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  failedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  initiatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  pendingAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  processedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  queuedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  rejectedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  reversedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  scheduledAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  scheduledOn?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};