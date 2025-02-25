import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPaymentHandleEncryptedAmountResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleEncryptedAmountResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleEncryptedAmountResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantPaymentHandleEncryptedAmountFailureResponse'
    | 'DashboardGraphQLMerchantPaymentHandleEncryptedAmountSuccessResponse',
    ParentType,
    ContextType
  >;
};