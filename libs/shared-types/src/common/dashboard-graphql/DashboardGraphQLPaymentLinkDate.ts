import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentLinkDate = {
  __typename?: 'DashboardGraphQLPaymentLinkDate';
  cancelledAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  deletedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  expireBy?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};