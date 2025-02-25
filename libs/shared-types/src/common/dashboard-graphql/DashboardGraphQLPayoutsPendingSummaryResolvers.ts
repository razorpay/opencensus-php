import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPayoutsPendingSummaryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsPendingSummary'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsPendingSummary'],
> = {
  count?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};