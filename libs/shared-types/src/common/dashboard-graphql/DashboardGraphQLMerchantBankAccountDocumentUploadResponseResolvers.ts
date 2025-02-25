import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantBankAccountDocumentUploadResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankAccountDocumentUploadResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankAccountDocumentUploadResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantBankAccountDocumentUploadFailureResponse'
    | 'DashboardGraphQLMerchantBankAccountDocumentUploadSuccessResponse',
    ParentType,
    ContextType
  >;
};