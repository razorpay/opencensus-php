import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantVirtualAccountsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantVirtualAccountsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantVirtualAccountsResponse'],
> = {
  hasMore?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  limit?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  virtualAccounts?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVirtualAccount']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};