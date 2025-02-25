import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLInvoiceDate = {
  __typename?: 'DashboardGraphQLInvoiceDate';
  cancelledAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  expireBy?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  issuedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  paidAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};