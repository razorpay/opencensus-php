import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLPaymentPage, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaymentPagesResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentPagesResponse';
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  paymentPages: Array<DashboardGraphQLPaymentPage>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};