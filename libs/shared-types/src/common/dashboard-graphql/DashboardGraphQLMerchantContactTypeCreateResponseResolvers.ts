import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantContactTypeCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactTypeCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactTypeCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantContactTypeCreateResponseDuplicate' | 'DashboardGraphQLMerchantContactTypeCreateResponseSuccess',
    ParentType,
    ContextType
  >;
};