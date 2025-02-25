import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentInstantRefundEligibilityResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentInstantRefundEligibilityResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentInstantRefundEligibilityResponse'],
> = {
  isAllowed?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  messages?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>, ParentType, ContextType>;
  option?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentInstantRefundEligibilityOptionEnum'],
    ParentType,
    ContextType
  >;
  refund?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentInstantRefundEligibilityAmount']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};