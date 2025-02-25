import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantCreditBalanceResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantCreditBalanceResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantCreditBalanceResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantCreditBalanceFailureResponse' | 'DashboardGraphQLMerchantCreditBalanceSuccessResponse',
    ParentType,
    ContextType
  >;
};