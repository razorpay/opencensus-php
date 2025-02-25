import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLModularOnboardingStepResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLModularOnboardingStep'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLModularOnboardingStep'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLModularOnboardingStepWithModularComponents' | 'DashboardGraphQLModularOnboardingStepWithSteps',
    ParentType,
    ContextType
  >;
};