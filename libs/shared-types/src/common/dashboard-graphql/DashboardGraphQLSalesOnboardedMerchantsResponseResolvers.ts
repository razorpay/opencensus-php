import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLSalesOnboardedMerchantsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLSalesOnboardedMerchantsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLSalesOnboardedMerchantsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLSalesOnboardedMerchants' | 'DashboardGraphQLSalesOnboardedMerchantsError',
    ParentType,
    ContextType
  >;
};