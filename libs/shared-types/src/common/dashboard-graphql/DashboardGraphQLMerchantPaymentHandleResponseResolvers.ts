import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPaymentHandleResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantPaymentHandleFailureResponse' | 'DashboardGraphQLMerchantPaymentHandleSuccessResponse',
    ParentType,
    ContextType
  >;
};