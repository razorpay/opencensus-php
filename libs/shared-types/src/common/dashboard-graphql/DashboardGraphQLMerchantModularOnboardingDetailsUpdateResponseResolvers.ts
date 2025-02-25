import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantModularOnboardingDetailsUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsUpdateResponse'] = DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'merchantModularOnboardingDetailsFailureResponse'
    | 'merchantModularOnboardingDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};