import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantConsentResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantConsentResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantConsentResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantConsentFailure' | 'DashboardGraphQLMerchantConsentSuccess',
    ParentType,
    ContextType
  >;
};