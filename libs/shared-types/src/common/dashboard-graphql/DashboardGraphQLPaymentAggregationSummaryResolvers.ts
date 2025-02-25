import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentAggregationSummaryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentAggregationSummary'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentAggregationSummary'],
> = {
  count?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  sum?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};