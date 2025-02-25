import { DashboardGraphQLScalars, DashboardGraphQLPaymentsWidgetTypeEnum, DashboardGraphQLWidgetVariantEnum } from './index';
export type DashboardGraphQLSettlementsWidget = {
  __typename?: 'DashboardGraphQLSettlementsWidget';
  description: DashboardGraphQLScalars['String'];
  title: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
  variant: DashboardGraphQLWidgetVariantEnum;
};