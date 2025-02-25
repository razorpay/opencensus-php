import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorPasswordEnabledResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorPasswordEnabledResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorPasswordEnabledResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorPasswordEnabledErrorResponse' | 'DashboardGraphQLTwoFactorPasswordEnabledSuccessResponse',
    ParentType,
    ContextType
  >;
};