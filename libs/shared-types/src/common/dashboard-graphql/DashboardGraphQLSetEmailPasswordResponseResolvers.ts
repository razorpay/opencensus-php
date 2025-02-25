import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLSetEmailPasswordResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLSetEmailPasswordResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLSetEmailPasswordResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLSetEmailPasswordErrorResponse' | 'DashboardGraphQLSetEmailPasswordSuccessResponse',
    ParentType,
    ContextType
  >;
};