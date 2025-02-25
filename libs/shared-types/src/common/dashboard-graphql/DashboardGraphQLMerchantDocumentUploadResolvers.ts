import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantDocumentUploadResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentUpload'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentUpload'],
> = {
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  displayName?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  mimeType?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  purpose?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantDocumentUploadPurposeEnum'], ParentType, ContextType>;
  size?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};