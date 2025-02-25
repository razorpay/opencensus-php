import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes } from './index';
export type DashboardGraphQLModularOnboardingStepInterfaceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLModularOnboardingStepInterface'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLModularOnboardingStepInterface'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLModularOnboardingStepWithModularComponents' | 'DashboardGraphQLModularOnboardingStepWithSteps',
    ParentType,
    ContextType
  >;
  meta?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLStepMeta']>, ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  progress?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Float'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
};