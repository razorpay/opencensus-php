import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLUserExistsByEmailOrPhoneResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLUserExistsByEmailOrPhoneResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLUserExistsByEmailOrPhoneResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLUserExistsByEmailOrPhoneError' | 'DashboardGraphQLUserExistsByEmailOrPhoneSuccess',
    ParentType,
    ContextType
  >;
};