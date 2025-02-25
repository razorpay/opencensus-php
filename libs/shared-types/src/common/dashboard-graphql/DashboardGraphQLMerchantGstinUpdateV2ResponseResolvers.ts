import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantGstinUpdateV2ResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantGstinUpdateV2Response'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantGstinUpdateV2Response'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantGstinUpdateV2FailureResponse' | 'DashboardGraphQLMerchantGstinUpdateV2SuccessResponse',
    ParentType,
    ContextType
  >;
};