import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantBankDetailsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankDetailsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankDetailsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantBankDetailsFailureResponse' | 'DashboardGraphQLMerchantBankDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};