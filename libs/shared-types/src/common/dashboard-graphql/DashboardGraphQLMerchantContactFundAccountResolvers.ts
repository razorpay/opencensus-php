import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantContactFundAccountResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactFundAccount'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactFundAccount'],
> = {
  active?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  batchId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  contact?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContact'], ParentType, ContextType>;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  details?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccountDetails']>,
    ParentType,
    ContextType
  >;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccountTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};