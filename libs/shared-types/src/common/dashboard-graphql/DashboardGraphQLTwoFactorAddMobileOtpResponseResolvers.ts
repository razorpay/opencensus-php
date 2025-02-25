import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorAddMobileOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorAddMobileOtpResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorAddMobileOtpResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorAddMobileOtpErrorResponse' | 'DashboardGraphQLTwoFactorAddMobileOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};