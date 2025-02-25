import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWorkflowConfigTemplateResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigTemplate'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowConfigTemplate'],
> = {
  states?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowConfigState']>, ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowConfigTemplateTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};