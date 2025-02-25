import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentVirtualAccountDates = {
  __typename?: 'DashboardGraphQLPaymentVirtualAccountDates';
  closeBy?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  closedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
};