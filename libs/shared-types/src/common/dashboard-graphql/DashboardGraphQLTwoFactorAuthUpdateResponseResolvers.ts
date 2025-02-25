import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorAuthUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorAuthUpdateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorAuthUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorAuthUpdateFailureResponse' | 'DashboardGraphQLTwoFactorAuthUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};