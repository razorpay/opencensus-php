import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLModularOnboardingMilestoneResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLModularOnboardingMilestone'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLModularOnboardingMilestone'],
> = {
  canSubmit?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  meta?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMilestoneMeta']>, ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  progress?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Float'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  steps?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLModularOnboardingStep']>>>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};