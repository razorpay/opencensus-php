import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantBusinessCategoryIconResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessCategoryIcon'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessCategoryIcon'],
> = {
  iconData?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  iconType?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessCategoryIconEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};