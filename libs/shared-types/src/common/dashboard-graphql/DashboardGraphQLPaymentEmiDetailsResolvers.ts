import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentEmiDetailsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentEmiDetails'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentEmiDetails'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  duration?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Float'], ParentType, ContextType>;
  rate?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Float'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};