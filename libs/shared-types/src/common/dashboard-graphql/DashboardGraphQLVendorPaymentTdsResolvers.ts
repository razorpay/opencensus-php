import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLVendorPaymentTdsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['VendorPaymentTDS'] = DashboardGraphQLResolversParentTypes['VendorPaymentTDS'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  deductedAmount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  tdsCategory?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['TDSCategory'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};