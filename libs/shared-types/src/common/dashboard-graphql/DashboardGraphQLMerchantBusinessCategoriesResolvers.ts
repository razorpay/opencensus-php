import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn, DashboardGraphQLResolver, DashboardGraphQLResolversTypes } from './index';
export type DashboardGraphQLMerchantBusinessCategoriesResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessCategories'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessCategories'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantBusinessCategoriesResponse' | 'DashboardGraphQLMerchantBusinessCategoriesResponseV2',
    ParentType,
    ContextType
  >;
  categoryName?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  categoryValue?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  subCategories?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessSubCategory']>,
    ParentType,
    ContextType
  >;
};