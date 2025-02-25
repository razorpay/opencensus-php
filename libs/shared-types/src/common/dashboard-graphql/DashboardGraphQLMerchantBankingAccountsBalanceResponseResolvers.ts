import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantBankingAccountsBalanceResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankingAccountsBalanceResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankingAccountsBalanceResponse'],
> = {
  balances?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingAccountBalance']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};