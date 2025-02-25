import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantWebsitePublishResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsitePublishResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsitePublishResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantWebsitePublishFailureResponse' | 'DashboardGraphQLMerchantWebsitePublishSuccessResponse',
    ParentType,
    ContextType
  >;
};