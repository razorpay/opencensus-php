import { DashboardGraphQLMaybe, DashboardGraphQLAcceptPaymentsProduct, DashboardGraphQLScalars, DashboardGraphQLPaymentsWidgetTypeEnum, DashboardGraphQLWidgetVariantEnum } from './index';
export type DashboardGraphQLAcceptPaymentsWidget = {
  __typename?: 'DashboardGraphQLAcceptPaymentsWidget';
  products?: DashboardGraphQLMaybe<Array<DashboardGraphQLAcceptPaymentsProduct>>;
  title: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
  variant: DashboardGraphQLWidgetVariantEnum;
};