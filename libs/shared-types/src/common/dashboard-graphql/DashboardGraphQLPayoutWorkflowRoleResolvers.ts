import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPayoutWorkflowRoleResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutWorkflowRole'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutWorkflowRole'],
> = {
  checkers?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutWorkflowRoleChecker']>>,
    ParentType,
    ContextType
  >;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  reviewerCount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Int'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutWorkflowRoleTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};