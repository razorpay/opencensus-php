import { DashboardGraphQLPaymentAnalyticsAggregateByEnum, DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLPaymentAnalyticsFilterBy, DashboardGraphQLPaymentAnalyticsIntervalEnum } from './index';
export type DashboardGraphQLQueryPaymentAnalyticsArgs = {
  aggregateBy: DashboardGraphQLPaymentAnalyticsAggregateByEnum;
  columnName: DashboardGraphQLScalars['String'];
  filterBy?: DashboardGraphQLInputMaybe<DashboardGraphQLPaymentAnalyticsFilterBy>;
  fromDate: DashboardGraphQLScalars['DateTime'];
  indexName: DashboardGraphQLScalars['String'];
  interval?: DashboardGraphQLInputMaybe<DashboardGraphQLPaymentAnalyticsIntervalEnum>;
  toDate: DashboardGraphQLScalars['DateTime'];
};