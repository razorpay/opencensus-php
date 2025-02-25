import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLOnboardingWidgetResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOnboardingWidget'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOnboardingWidget'],
> = {
  title?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};