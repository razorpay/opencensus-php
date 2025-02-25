import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLLoginOtpResendResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLLoginOtpResendResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLLoginOtpResendResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLLoginOtpResendError' | 'DashboardGraphQLLoginOtpResendSuccess',
    ParentType,
    ContextType
  >;
};