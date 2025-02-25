import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPayoutApproveBulkResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutApproveBulkResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutApproveBulkResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLPayoutApproveBulkResponseFailure' | 'DashboardGraphQLPayoutApproveBulkResponseSuccess',
    ParentType,
    ContextType
  >;
};