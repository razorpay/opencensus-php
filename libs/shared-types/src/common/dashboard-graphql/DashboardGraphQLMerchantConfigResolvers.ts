import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantConfigResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantConfig'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantConfig'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantConfigFailure' | 'DashboardGraphQLMerchantOnboardingConfig',
    ParentType,
    ContextType
  >;
};