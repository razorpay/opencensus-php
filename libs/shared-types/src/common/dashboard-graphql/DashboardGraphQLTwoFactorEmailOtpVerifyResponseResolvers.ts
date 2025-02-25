import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTwoFactorEmailOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorEmailOtpVerifyResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTwoFactorEmailOtpVerifyResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLTwoFactorEmailOtpVerifyFailureResponse' | 'DashboardGraphQLTwoFactorEmailOtpVerifySuccessResponse',
    ParentType,
    ContextType
  >;
};