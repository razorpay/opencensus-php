import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLNotificationEmailUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLNotificationEmailUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLNotificationEmailUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLNotificationEmailUpdateFailureResponse' | 'DashboardGraphQLNotificationEmailUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};