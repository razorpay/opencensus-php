import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLPaymentLink, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaymentLinksResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentLinksResponse';
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  paymentLinks: Array<DashboardGraphQLPaymentLink>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};