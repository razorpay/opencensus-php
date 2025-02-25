import { DashboardGraphQLScalars, DashboardGraphQLPaymentsWidgetTypeEnum } from './index';
export type DashboardGraphQLPaymentsWidgetError = {
  __typename?: 'DashboardGraphQLPaymentsWidgetError';
  errorCode: DashboardGraphQLScalars['String'];
  errorDescription: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
};