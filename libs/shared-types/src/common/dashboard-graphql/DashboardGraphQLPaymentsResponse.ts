import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLPayment } from './index';
export type DashboardGraphQLPaymentsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentsResponse';
  hasMore: DashboardGraphQLScalars['Boolean'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  payments: Array<DashboardGraphQLPayment>;
  total: DashboardGraphQLScalars['NonNegativeInt'];
};