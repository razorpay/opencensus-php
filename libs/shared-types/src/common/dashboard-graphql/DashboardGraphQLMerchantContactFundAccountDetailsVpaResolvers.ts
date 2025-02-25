import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantContactFundAccountDetailsVpaResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['MerchantContactFundAccountDetailsVPA'] = DashboardGraphQLResolversParentTypes['MerchantContactFundAccountDetailsVPA'],
> = {
  address?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['VPA'], ParentType, ContextType>;
  handle?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};