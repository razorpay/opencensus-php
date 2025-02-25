import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantSupportDetailsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSupportDetailsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSupportDetailsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantSupportDetailsFailureResponse' | 'DashboardGraphQLMerchantSupportDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};