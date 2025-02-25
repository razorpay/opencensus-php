import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLSettlementAmountResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLSettlementAmount'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLSettlementAmount'],
> = {
  fee?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Float']>, ParentType, ContextType>;
  settlement?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  tax?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Float']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};