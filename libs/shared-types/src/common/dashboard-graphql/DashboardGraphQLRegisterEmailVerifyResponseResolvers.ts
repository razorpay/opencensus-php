import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLRegisterEmailVerifyResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterEmailVerifyResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterEmailVerifyResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLRegisterEmailVerifyResponseFailure' | 'DashboardGraphQLRegisterEmailVerifyResponseSuccess',
    ParentType,
    ContextType
  >;
};