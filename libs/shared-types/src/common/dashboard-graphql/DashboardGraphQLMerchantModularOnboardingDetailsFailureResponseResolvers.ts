import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantModularOnboardingDetailsFailureResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsFailureResponse'] = DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsFailureResponse'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Int'], ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};