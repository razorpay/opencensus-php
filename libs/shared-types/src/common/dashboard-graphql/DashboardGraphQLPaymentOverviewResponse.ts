import { DashboardGraphQLMaybe, DashboardGraphQLPaymentAggregationSummary } from './index';
export type DashboardGraphQLPaymentOverviewResponse = {
  __typename?: 'DashboardGraphQLPaymentOverviewResponse';
  paymentCollected?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAggregationSummary>;
  refundFailed?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAggregationSummary>;
  refundProcessed?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAggregationSummary>;
  refundProcessing?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAggregationSummary>;
};