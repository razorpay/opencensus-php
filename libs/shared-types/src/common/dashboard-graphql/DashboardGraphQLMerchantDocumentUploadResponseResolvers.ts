import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantDocumentUploadResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentUploadResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentUploadResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantDocumentUploadFailureResponse' | 'DashboardGraphQLMerchantDocumentUploadSuccessResponse',
    ParentType,
    ContextType
  >;
};