import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantContactFundAccountCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactFundAccountCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactFundAccountCreateResponse'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  fundAccount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccount'], ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};