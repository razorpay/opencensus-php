import { DashboardGraphQLPaymentsSegmentEnum, DashboardGraphQLWidget } from './index';
export type DashboardGraphQLPaymentsWidgets = {
  __typename?: 'DashboardGraphQLPaymentsWidgets';
  segment: DashboardGraphQLPaymentsSegmentEnum;
  widgets: Array<DashboardGraphQLWidget>;
};