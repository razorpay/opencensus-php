import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWorkflowDataResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowData'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWorkflowData'],
> = {
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  milestones?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLModularOnboardingMilestone']>>>,
    ParentType,
    ContextType
  >;
  progress?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Float'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};