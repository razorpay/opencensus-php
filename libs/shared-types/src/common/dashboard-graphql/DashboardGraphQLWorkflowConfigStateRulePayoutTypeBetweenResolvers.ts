import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWorkflowConfigStateRulePayoutTypeBetweenResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigStateRulePayoutTypeBetween'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigStateRulePayoutTypeBetween'],
> = {
  key?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  max?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  min?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};