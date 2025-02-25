import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLFailedPaymentsOverviewResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLFailedPaymentsOverviewResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLFailedPaymentsOverviewResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLFailedPaymentsOverviewFailureResponse' | 'DashboardGraphQLFailedPaymentsOverviewSuccessResponse',
    ParentType,
    ContextType
  >;
};