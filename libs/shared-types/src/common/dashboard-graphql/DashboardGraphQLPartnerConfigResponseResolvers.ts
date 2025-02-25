import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPartnerConfigResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPartnerConfigResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPartnerConfigResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLPartnerConfigFailure' | 'DashboardGraphQLPartnerConfigSuccess',
    ParentType,
    ContextType
  >;
};