import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLLoginOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLLoginOtpResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLLoginOtpResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<'DashboardGraphQLLoginOtpError' | 'DashboardGraphQLLoginOtpSuccess', ParentType, ContextType>;
};