import { DashboardGraphQLPaymentAnalyticsAggregateByEnum, DashboardGraphQLMaybe, DashboardGraphQLPaymentAnalytics, DashboardGraphQLPaymentAnalyticsIntervalEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentAnalyticsResponse = {
  __typename?: 'DashboardGraphQLPaymentAnalyticsResponse';
  aggregatedBy: DashboardGraphQLPaymentAnalyticsAggregateByEnum;
  analytics?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLPaymentAnalytics>>>;
  interval?: DashboardGraphQLMaybe<DashboardGraphQLPaymentAnalyticsIntervalEnum>;
  updatedAt: DashboardGraphQLScalars['DateTime'];
};