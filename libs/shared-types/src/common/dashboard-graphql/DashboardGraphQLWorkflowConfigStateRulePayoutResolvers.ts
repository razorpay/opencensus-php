import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLWorkflowConfigStateRulePayoutResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigStateRulePayout'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigStateRulePayout'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLWorkflowConfigStateRulePayoutTypeBetween'
    | 'DashboardGraphQLWorkflowConfigStateRulePayoutTypeChecker'
    | 'DashboardGraphQLWorkflowConfigStateRulePayoutTypeMergeStates',
    ParentType,
    ContextType
  >;
};