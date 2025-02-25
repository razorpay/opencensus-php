import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantOnboardingConfigResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantOnboardingConfig'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantOnboardingConfig'],
> = {
  configData?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLConfigData'], ParentType, ContextType>;
  configType?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};