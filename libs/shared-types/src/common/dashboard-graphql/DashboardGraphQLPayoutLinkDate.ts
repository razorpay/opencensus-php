import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPayoutLinkDate = {
  __typename?: 'DashboardGraphQLPayoutLinkDate';
  cancelledAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};