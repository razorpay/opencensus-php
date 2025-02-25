import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLVendorPayment } from './index';
export type DashboardGraphQLVendorPaymentsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLVendorPaymentsResponse';
  hasMore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  vendorPayments: Array<DashboardGraphQLVendorPayment>;
};