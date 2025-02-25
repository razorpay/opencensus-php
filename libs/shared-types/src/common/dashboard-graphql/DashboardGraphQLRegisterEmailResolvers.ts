import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLRegisterEmailResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterEmail'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterEmail'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLRegisterEmailError' | 'DashboardGraphQLRegisterEmailSuccess',
    ParentType,
    ContextType
  >;
};