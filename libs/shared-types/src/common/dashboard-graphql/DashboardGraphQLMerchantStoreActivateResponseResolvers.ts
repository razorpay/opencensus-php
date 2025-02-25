import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantStoreActivateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStoreActivateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStoreActivateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantStoreActivateFailureResponse' | 'DashboardGraphQLMerchantStoreActivateSuccessResponse',
    ParentType,
    ContextType
  >;
};