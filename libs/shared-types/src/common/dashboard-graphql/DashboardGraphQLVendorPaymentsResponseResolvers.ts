import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLVendorPaymentsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLVendorPaymentsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLVendorPaymentsResponse'],
> = {
  hasMore?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  limit?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  vendorPayments?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLVendorPayment']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};