import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantWorkflowClarificationSubmitResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWorkflowClarificationSubmitResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWorkflowClarificationSubmitResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantWorkflowClarificationSubmitFailureResponse'
    | 'DashboardGraphQLMerchantWorkflowClarificationSubmitSuccessResponse',
    ParentType,
    ContextType
  >;
};