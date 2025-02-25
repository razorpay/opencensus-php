import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantSelfServeWorkflowStatusResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSelfServeWorkflowStatusResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSelfServeWorkflowStatusResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantSelfServeWorkflowStatusFailureResponse'
    | 'DashboardGraphQLMerchantSelfServeWorkflowStatusSuccessResponse',
    ParentType,
    ContextType
  >;
};