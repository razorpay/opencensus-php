import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantApiKeyInterfaceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantApiKeyInterface'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantApiKeyInterface'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantApiKey'
    | 'DashboardGraphQLMerchantApiKeyCreateResponse'
    | 'DashboardGraphQLMerchantApiKeyRegenerateNew'
    | 'DashboardGraphQLMerchantApiKeyRegenerateOld'
    | 'DashboardGraphQLMerchantApiKeysCreateSuccess',
    ParentType,
    ContextType
  >;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
};