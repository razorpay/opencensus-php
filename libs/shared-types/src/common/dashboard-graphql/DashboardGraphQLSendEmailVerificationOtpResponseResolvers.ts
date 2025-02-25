import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLSendEmailVerificationOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLSendEmailVerificationOtpResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLSendEmailVerificationOtpResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLSendEmailVerificationOtpErrorResponse' | 'DashboardGraphQLSendEmailVerificationOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};