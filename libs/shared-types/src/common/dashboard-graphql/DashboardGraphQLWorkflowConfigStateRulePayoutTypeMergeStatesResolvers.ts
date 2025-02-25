import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWorkflowConfigStateRulePayoutTypeMergeStatesResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigStateRulePayoutTypeMergeStates'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigStateRulePayoutTypeMergeStates'],
> = {
  states?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};