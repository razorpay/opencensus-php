import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPolicyPreviewResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPreviewResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPreviewResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantPolicyEmptyPreviewResponse'
    | 'DashboardGraphQLMerchantPolicyPreviewFailureResponse'
    | 'DashboardGraphQLMerchantPolicyPreviewSuccessResponse',
    ParentType,
    ContextType
  >;
};