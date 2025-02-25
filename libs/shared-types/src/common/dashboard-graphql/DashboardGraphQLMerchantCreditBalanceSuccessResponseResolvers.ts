import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantCreditBalanceSuccessResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantCreditBalanceSuccessResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantCreditBalanceSuccessResponse'],
> = {
  balanceDetails?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantCreditBalance'], ParentType, ContextType>;
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};