import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentMethodEmiResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentMethodEmi'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentMethodEmi'],
> = {
  card?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentMethodCard'], ParentType, ContextType>;
  emi?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentEmiDetails'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};