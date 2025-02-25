import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorAddMobileOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorAddMobileOtpVerifyResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorAddMobileOtpVerifyResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorAddMobileOtpVerifyErrorResponse' | 'DashboardGraphQLTwoFactorAddMobileOtpVerifySuccessResponse',
    ParentType,
    ContextType
  >;
};