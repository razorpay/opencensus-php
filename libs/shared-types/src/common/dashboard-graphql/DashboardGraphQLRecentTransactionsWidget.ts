import { DashboardGraphQLScalars, DashboardGraphQLPaymentsWidgetTypeEnum, DashboardGraphQLWidgetVariantEnum } from './index';
export type DashboardGraphQLRecentTransactionsWidget = {
  __typename?: 'DashboardGraphQLRecentTransactionsWidget';
  title: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
  variant: DashboardGraphQLWidgetVariantEnum;
};