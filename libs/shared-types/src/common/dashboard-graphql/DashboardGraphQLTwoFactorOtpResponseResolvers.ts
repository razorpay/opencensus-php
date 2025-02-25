import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorOtpResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorOtpResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorOtpFailureResponse' | 'DashboardGraphQLTwoFactorOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};