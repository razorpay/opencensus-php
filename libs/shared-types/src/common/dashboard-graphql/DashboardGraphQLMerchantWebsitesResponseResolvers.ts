import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantWebsitesResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsitesResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsitesResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantWebsiteDetailsFailureResponse' | 'DashboardGraphQLMerchantWebsiteDetailsResponse',
    ParentType,
    ContextType
  >;
};