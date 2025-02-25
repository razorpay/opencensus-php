import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLRejectPayoutBatchResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLRejectPayoutBatchResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLRejectPayoutBatchResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLRejectPayoutBatchResponseFailure' | 'DashboardGraphQLRejectPayoutBatchResponseSuccess',
    ParentType,
    ContextType
  >;
};