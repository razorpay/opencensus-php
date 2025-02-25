import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLVendorPaymentDates = {
  __typename?: 'DashboardGraphQLVendorPaymentDates';
  cancelledAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  createdAt: DashboardGraphQLScalars['DateTime'];
  draftCreatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  dueOn?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  invoiceIssuedAt: DashboardGraphQLScalars['DateTime'];
  paidAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  unpaidAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  updatedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};