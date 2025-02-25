import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLVerifyEmailOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLVerifyEmailOtpResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLVerifyEmailOtpResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLVerifyEmailOtpErrorResponse' | 'DashboardGraphQLVerifyEmailOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};