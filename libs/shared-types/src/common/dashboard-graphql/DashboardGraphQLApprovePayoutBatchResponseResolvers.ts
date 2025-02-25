import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLApprovePayoutBatchResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLApprovePayoutBatchResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLApprovePayoutBatchResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLApprovePayoutBatchResponseFailure' | 'DashboardGraphQLApprovePayoutBatchResponseSuccess',
    ParentType,
    ContextType
  >;
};