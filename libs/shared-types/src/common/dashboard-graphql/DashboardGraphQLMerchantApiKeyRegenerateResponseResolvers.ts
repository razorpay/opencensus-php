import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantApiKeyRegenerateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantApiKeyRegenerateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantApiKeyRegenerateResponse'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  newApiKey?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantApiKeyRegenerateNew'], ParentType, ContextType>;
  oldApiKey?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantApiKeyRegenerateOld'], ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};