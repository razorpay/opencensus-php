import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLAuthResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLAuth'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLAuth'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLAuthUnauthenticated' | 'DashboardGraphQLAuthUnregistered' | 'DashboardGraphQLAuthUser',
    ParentType,
    ContextType
  >;
};