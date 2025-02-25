import { DashboardGraphQLScalars, DashboardGraphQLPaymentsWidgetTypeEnum, DashboardGraphQLWidgetVariantEnum } from './index';
export type DashboardGraphQLPaymentAnalyticsWidget = {
  __typename?: 'DashboardGraphQLPaymentAnalyticsWidget';
  title: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
  variant: DashboardGraphQLWidgetVariantEnum;
};