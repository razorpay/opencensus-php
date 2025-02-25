import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantPolicyPreviewV2ResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPreviewV2Response'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPreviewV2Response'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantPolicyEmptyV2PreviewResponse'
    | 'DashboardGraphQLMerchantPolicyPreviewV2FailureResponse'
    | 'DashboardGraphQLMerchantPolicyPreviewV2SuccessResponse',
    ParentType,
    ContextType
  >;
};