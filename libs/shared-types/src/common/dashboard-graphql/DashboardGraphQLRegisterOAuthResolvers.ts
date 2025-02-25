import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLRegisterOAuthResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterOAuth'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterOAuth'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLAuthUser'
    | 'DashboardGraphQLRegisterOAuthEmailError'
    | 'DashboardGraphQLRegisterOAuthEmailExist'
    | 'DashboardGraphQLRegisterOAuthInvalidTokenError',
    ParentType,
    ContextType
  >;
};