import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLInvoice } from './index';
export type DashboardGraphQLInvoicesResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLInvoicesResponse';
  hasMore: DashboardGraphQLScalars['Boolean'];
  invoices: Array<DashboardGraphQLInvoice>;
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total: DashboardGraphQLScalars['NonNegativeInt'];
};