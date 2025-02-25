import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantApiKeysCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantApiKeysCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantApiKeysCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantApiKeysCreateFailure' | 'DashboardGraphQLMerchantApiKeysCreateSuccess',
    ParentType,
    ContextType
  >;
};