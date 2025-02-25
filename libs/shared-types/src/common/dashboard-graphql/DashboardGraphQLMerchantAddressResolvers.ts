import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantAddressResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantAddress'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantAddress'],
> = {
  city?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  country?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  district?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  line1?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  line2?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  state?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  zipCode?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStringField'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};