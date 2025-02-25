import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPaymentHandleCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantPaymentHandleCreateFailureResponse' | 'DashboardGraphQLMerchantPaymentHandleCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};