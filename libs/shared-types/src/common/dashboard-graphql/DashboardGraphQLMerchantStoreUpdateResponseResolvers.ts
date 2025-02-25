import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantStoreUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStoreUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStoreUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantStoreUpdateFailureResponse' | 'DashboardGraphQLMerchantStoreUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};