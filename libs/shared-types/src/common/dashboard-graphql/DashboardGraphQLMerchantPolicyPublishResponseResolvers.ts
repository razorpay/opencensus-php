import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPolicyPublishResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPublishResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPublishResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantPolicyPublishFailureResponse' | 'DashboardGraphQLMerchantPolicyPublishSuccessResponse',
    ParentType,
    ContextType
  >;
};