import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantConsentsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantConsentsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantConsentsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantConsentsFailureResponse' | 'DashboardGraphQLMerchantConsentsSuccessResponse',
    ParentType,
    ContextType
  >;
};