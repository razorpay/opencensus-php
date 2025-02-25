import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLRequireFields, DashboardGraphQLMerchantBalanceVirtualAccountsResponseArgs, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantBalanceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBalance'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBalance'],
> = {
  accountType?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBalanceAccountTypeEnum']>,
    ParentType,
    ContextType
  >;
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  productType?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBalanceProductTypeEnum'], ParentType, ContextType>;
  virtualAccountsResponse?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVirtualAccountsResponse']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMerchantBalanceVirtualAccountsResponseArgs,
      'virtualAccountsLimit' | 'virtualAccountsOffset'
    >
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};