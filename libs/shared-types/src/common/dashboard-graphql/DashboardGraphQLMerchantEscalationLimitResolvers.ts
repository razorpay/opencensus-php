import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantEscalationLimitResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEscalationLimit'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEscalationLimit'],
> = {
  milestone?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationMilestoneEnum'], ParentType, ContextType>;
  threshold?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};