import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantShopEstablishmentResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantShopEstablishment'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantShopEstablishment'],
> = {
  isVerifiableZone?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  number?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};