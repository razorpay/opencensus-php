import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLVendorPaymentGstResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['VendorPaymentGST'] = DashboardGraphQLResolversParentTypes['VendorPaymentGST'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  gstin?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLGstTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};