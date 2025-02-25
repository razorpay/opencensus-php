import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantModularOnboardingDetailsSuccessResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsSuccessResponse'] = DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsSuccessResponse'],
> = {
  countryCode?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  merchantType?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  onboardingState?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLOnboardingState'], ParentType, ContextType>;
  onboardingType?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  workflowData?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowData'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};