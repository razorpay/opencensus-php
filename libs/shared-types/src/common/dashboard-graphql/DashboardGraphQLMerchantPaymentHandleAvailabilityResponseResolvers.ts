import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPaymentHandleAvailabilityResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleAvailabilityResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentHandleAvailabilityResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantPaymentHandleAvailabilityFailureResponse'
    | 'DashboardGraphQLMerchantPaymentHandleAvailabilitySuccessResponse',
    ParentType,
    ContextType
  >;
};