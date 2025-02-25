import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaymentPageDate = {
  __typename?: 'DashboardGraphQLPaymentPageDate';
  createdAt: DashboardGraphQLScalars['DateTime'];
  expireBy?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};