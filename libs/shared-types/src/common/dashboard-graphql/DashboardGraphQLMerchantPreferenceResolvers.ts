import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantPreferenceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPreference'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPreference'],
> = {
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  group?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  merchantId?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  productType?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPreferenceProductTypeEnum'],
    ParentType,
    ContextType
  >;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  value?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};