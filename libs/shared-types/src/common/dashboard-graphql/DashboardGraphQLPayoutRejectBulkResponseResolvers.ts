import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPayoutRejectBulkResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutRejectBulkResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutRejectBulkResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLPayoutRejectBulkResponseFailure' | 'DashboardGraphQLPayoutRejectBulkResponseSuccess',
    ParentType,
    ContextType
  >;
};