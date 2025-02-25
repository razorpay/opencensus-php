import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantEmailUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEmailUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEmailUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantEmailUpdateFailureResponse' | 'DashboardGraphQLMerchantEmailUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};