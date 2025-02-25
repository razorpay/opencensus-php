import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPayoutWorkflowResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutWorkflow'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutWorkflow'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<'DashboardGraphQLPayoutWorkflowHistory' | 'DashboardGraphQLWorkflow', ParentType, ContextType>;
};