import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorPasswordCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorPasswordCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorPasswordCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorPasswordCreateErrorResponse' | 'DashboardGraphQLTwoFactorPasswordCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};