import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPointOfSalePaymentCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPointOfSalePaymentCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPointOfSalePaymentCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLPointOfSalePaymentCreateFailureResponse' | 'DashboardGraphQLPointOfSalePaymentCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};