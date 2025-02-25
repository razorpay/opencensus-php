import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantEscalationsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEscalations'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEscalations'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantActivationEscalationsBreached' | 'DashboardGraphQLMerchantActivationEscalationsNotBreached',
    ParentType,
    ContextType
  >;
};