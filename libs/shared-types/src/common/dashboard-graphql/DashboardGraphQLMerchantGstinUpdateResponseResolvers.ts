import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantGstinUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantGstinUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantGstinUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantGstinUpdateAsyncFlowSuccessResponse'
    | 'DashboardGraphQLMerchantGstinUpdateFailureResponse'
    | 'DashboardGraphQLMerchantGstinUpdateInSyncFlowResponse'
    | 'DashboardGraphQLMerchantGstinUpdateInSyncWorkFlowCreatedResponse',
    ParentType,
    ContextType
  >;
};