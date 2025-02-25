import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantStoreDeactivateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStoreDeactivateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStoreDeactivateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantStoreDeactivateFailureResponse' | 'DashboardGraphQLMerchantStoreDeactivateSuccessResponse',
    ParentType,
    ContextType
  >;
};