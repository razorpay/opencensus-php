import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLWidgetResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWidget'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWidget'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLAcceptPaymentsWidget'
    | 'DashboardGraphQLOnboardingWidget'
    | 'DashboardGraphQLPaymentAnalyticsWidget'
    | 'DashboardGraphQLPaymentHandleWidget'
    | 'DashboardGraphQLPaymentsWidgetError'
    | 'DashboardGraphQLRecentTransactionsWidget'
    | 'DashboardGraphQLSettlementsWidget',
    ParentType,
    ContextType
  >;
};