import { DashboardGraphQLScalars, DashboardGraphQLPaymentsWidgetTypeEnum, DashboardGraphQLWidgetVariantEnum } from './index';
export type DashboardGraphQLOnboardingWidget = {
  __typename?: 'DashboardGraphQLOnboardingWidget';
  title: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
  variant: DashboardGraphQLWidgetVariantEnum;
};