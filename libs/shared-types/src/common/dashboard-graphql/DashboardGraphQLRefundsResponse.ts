import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLPaymentRefund } from './index';
export type DashboardGraphQLRefundsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLRefundsResponse';
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  refunds: Array<DashboardGraphQLPaymentRefund>;
  total: DashboardGraphQLScalars['NonNegativeInt'];
};