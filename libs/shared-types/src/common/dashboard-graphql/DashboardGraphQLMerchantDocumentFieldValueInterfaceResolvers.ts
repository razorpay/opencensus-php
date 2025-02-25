import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes } from './index';
export type DashboardGraphQLMerchantDocumentFieldValueInterfaceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentFieldValueInterface'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentFieldValueInterface'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantDocumentByIdResponse' | 'DashboardGraphQLMerchantDocumentFieldValue',
    ParentType,
    ContextType
  >;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  fileName?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
};