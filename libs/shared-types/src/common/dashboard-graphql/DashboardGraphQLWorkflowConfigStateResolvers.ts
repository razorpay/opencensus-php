import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWorkflowConfigStateResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigState'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigState'],
> = {
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  rule?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowConfigStateRulePayout'], ParentType, ContextType>;
  transition?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowConfigStateTransition'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowConfigStateTypeEnum']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};