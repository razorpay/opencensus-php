import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLSortByEnum, DashboardGraphQLVendorPaymentStatusEnum } from './index';
export type DashboardGraphQLQueryVendorPaymentsArgs = {
  contactName?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  invoiceNumber?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  sortBy?: DashboardGraphQLInputMaybe<DashboardGraphQLSortByEnum>;
  statuses?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLVendorPaymentStatusEnum>>;
};