import { DashboardGraphQLAcceptPaymentsWidget, DashboardGraphQLOnboardingWidget, DashboardGraphQLPaymentAnalyticsWidget, DashboardGraphQLPaymentHandleWidget, DashboardGraphQLPaymentsWidgetError, DashboardGraphQLRecentTransactionsWidget, DashboardGraphQLSettlementsWidget } from './index';
export type DashboardGraphQLWidget =
  | DashboardGraphQLAcceptPaymentsWidget
  | DashboardGraphQLOnboardingWidget
  | DashboardGraphQLPaymentAnalyticsWidget
  | DashboardGraphQLPaymentHandleWidget
  | DashboardGraphQLPaymentsWidgetError
  | DashboardGraphQLRecentTransactionsWidget
  | DashboardGraphQLSettlementsWidget;