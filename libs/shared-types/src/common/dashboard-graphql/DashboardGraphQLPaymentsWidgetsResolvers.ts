import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentsWidgetsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentsWidgets'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentsWidgets'],
> = {
  segment?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsSegmentEnum'], ParentType, ContextType>;
  widgets?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLWidget']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};