import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantBusinessAddressResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessAddress'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessAddress'],
> = {
  operation?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAddress'], ParentType, ContextType>;
  registered?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAddress'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};