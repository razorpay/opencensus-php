import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantBusinessTypesResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessTypesResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessTypesResponse'],
> = {
  registered?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLBusinessType']>, ParentType, ContextType>;
  unregistered?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLBusinessType']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};