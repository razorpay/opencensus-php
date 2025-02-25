import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantModularOnboardingDetailsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsResponse'] = DashboardGraphQLResolversParentTypes['merchantModularOnboardingDetailsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'merchantModularOnboardingDetailsFailureResponse'
    | 'merchantModularOnboardingDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};