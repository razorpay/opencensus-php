import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPointOfSalePaymentUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPointOfSalePaymentUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPointOfSalePaymentUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLPointOfSalePaymentUpdateFailureResponse' | 'DashboardGraphQLPointOfSalePaymentUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};