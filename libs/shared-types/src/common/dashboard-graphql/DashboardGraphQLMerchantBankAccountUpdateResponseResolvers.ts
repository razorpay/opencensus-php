import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantBankAccountUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankAccountUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBankAccountUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantBankAccountUpdateFailureResponse' | 'DashboardGraphQLMerchantBankAccountUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};