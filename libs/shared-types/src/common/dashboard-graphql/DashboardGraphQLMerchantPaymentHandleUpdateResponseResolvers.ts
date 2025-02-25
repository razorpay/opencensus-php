import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPaymentHandleUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantPaymentHandleUpdateFailureResponse' | 'DashboardGraphQLMerchantPaymentHandleUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};