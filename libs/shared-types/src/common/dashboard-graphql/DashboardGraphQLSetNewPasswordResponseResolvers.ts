import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLSetNewPasswordResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLSetNewPasswordResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLSetNewPasswordResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLSetNewPasswordErrorResponse' | 'DashboardGraphQLSetNewPasswordSuccessResponse',
    ParentType,
    ContextType
  >;
};