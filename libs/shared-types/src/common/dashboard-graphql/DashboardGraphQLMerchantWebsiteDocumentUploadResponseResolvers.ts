import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantWebsiteDocumentUploadResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteDocumentUploadResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteDocumentUploadResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantWebsiteDocumentUploadFailureResponse' | 'DashboardGraphQLMerchantWebsiteDocumentUploadSuccessResponse',
    ParentType,
    ContextType
  >;
};