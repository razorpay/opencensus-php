import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLOnboardingPaymentOrderCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOnboardingPaymentOrderCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOnboardingPaymentOrderCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLOnboardingPaymentOrderCreateFailureResponse' | 'DashboardGraphQLOnboardingPaymentOrderCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};