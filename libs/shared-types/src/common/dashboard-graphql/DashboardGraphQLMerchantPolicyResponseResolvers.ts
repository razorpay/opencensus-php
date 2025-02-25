import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPolicyResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantPolicyEmptyResponse'
    | 'DashboardGraphQLMerchantPolicyFailureResponse'
    | 'DashboardGraphQLMerchantPolicySuccessResponse',
    ParentType,
    ContextType
  >;
};