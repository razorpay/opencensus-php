import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantWebsiteDocumentDeleteResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteDocumentDeleteResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteDocumentDeleteResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantWebsiteDocumentDeleteFailureResponse' | 'DashboardGraphQLMerchantWebsiteDocumentDeleteSuccessResponse',
    ParentType,
    ContextType
  >;
};