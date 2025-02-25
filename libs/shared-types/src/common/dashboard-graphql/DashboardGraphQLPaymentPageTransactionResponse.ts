import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPaymentPageTransaction } from './index';
export type DashboardGraphQLPaymentPageTransactionResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentPageTransactionResponse';
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  paymentPagesTransactions?: DashboardGraphQLMaybe<Array<DashboardGraphQLPaymentPageTransaction>>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};