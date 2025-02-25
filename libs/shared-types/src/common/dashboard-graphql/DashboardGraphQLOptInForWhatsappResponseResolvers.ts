import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLOptInForWhatsappResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOptInForWhatsappResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOptInForWhatsappResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLOptInForWhatsappError' | 'DashboardGraphQLOptInForWhatsappSuccess',
    ParentType,
    ContextType
  >;
};